<?php

declare(strict_types=1);

namespace App\Service\Search\Result;

use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\ResultFactory;
use App\Entity\Inquiry;
use App\Service\Search\Model\Aggregation;
use App\Service\Search\Model\Config;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Model\Suggestion;
use App\Service\Search\Model\SuggestionEntry;
use App\Service\Search\Query\Facet\Input\DateFacetInput;
use App\Service\Search\Query\Facet\Input\StringValuesFacetInput;
use App\ValueObject\FilterDetails;
use App\ValueObject\InquiryDescription;
use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Jaytaph\TypeArray\TypeArray;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResultTransformer
{
    public function __construct(
        protected EntityManagerInterface $doctrine,
        protected LoggerInterface $logger,
        protected PaginatorInterface $paginator,
        private readonly AggregationMapper $aggregationMapper,
        private readonly ResultFactory $resultFactory,
    ) {
    }

    /**
     * @param array<string,mixed> $query
     */
    public function transform(array $query, Config $config, ?Elasticsearch $response): Result
    {
        if (! $response) {
            $this->logger->error('ElasticSearch did not return a response', [
                'query' => $query,
            ]);

            return Result::create()
                ->setFailed(true)
                ->setMessage('No response from ElasticSearch')
                ->setQuery($query);
        }

        // Populate result object with search and config data
        $result = $this->transformResults($config, $response);
        $result->setQuery($query);
        $result->setType($config->searchType);

        // Copy limit/offset to the result, so we can create pagination
        $result->setOffset($config->offset);
        $result->setLimit($config->limit);

        if ($config->pagination && $result->getLimit() > 0) {
            /** @var PaginationInterface<int,AbstractPagination> $pagination */
            $pagination = $this->paginator->paginate(
                target: $result,
                page: $config->limit > 0 ? ($config->offset / $config->limit + 1) : 1,
                limit: $config->limit
            );
            $result->setPagination($pagination);
        }

        $result->setFilterDetails($this->getFilterDetails($config));

        return $result;
    }

    /**
     * Transforms the elasticsearch result array into a result object.
     */
    protected function transformResults(Config $config, Elasticsearch $response): Result
    {
        $typedResponse = new TypeArray($response->asArray());
        $result = new Result();

        $result->setTimeTaken($typedResponse->getInt('[took]', 0));
        if ($typedResponse->getBool('[timed_out]')) {
            $result->setFailed(true);
            $result->setMessage('ElasticSearch timed out');

            return $result;
        }

        $result->setResultCount($typedResponse->getInt('[hits][total][value]', 0));
        $result->setDossierCount($typedResponse->getInt('[aggregations][unique_dossiers][value]', 0));

        $documentCountWithoutDate = $typedResponse->getIntOrNull('[aggregations][all][facet-base-filter][date_filter][doc_count]')
            ?? $typedResponse->getIntOrNull('[aggregations][all][facet-base-filter][facet-filter-date][date_filter][doc_count]');
        $result->setDocumentCountWithoutDate($documentCountWithoutDate);
        $result->setDisplayWithoutDateMessage($this->displayWithoutDateMessage($config, $documentCountWithoutDate));

        $suggestions = $this->transformSuggestions($typedResponse);
        if ($suggestions) {
            $result->setSuggestions($suggestions);
        }

        if ($config->aggregations) {
            $aggregations = $this->transformAggregations($typedResponse->getTypeArray('[aggregations]'));
            if ($aggregations) {
                $result->setAggregations($aggregations);
            }
        }

        // Add all found hits and their documents
        $entries = [];
        foreach ($typedResponse->getIterable('[hits][hits]') as $hit) {
            $entries[] = $this->resultFactory->map($hit);
        }

        /** @var ResultEntryInterface[] $entries */
        $result->setEntries(array_filter($entries));

        return $result;
    }

    /**
     * @return Suggestion[]
     */
    protected function transformSuggestions(TypeArray $response): array
    {
        if (! $response->exists('[suggest]')) {
            return [];
        }

        $ret = [];
        foreach ($response->getIterable('[suggest]') as $name => $entry) {
            foreach ($entry->toArray() as $suggestion) {
                $suggestion = new TypeArray($suggestion);

                $entries = [];
                /** @var TypeArray $option */
                foreach ($suggestion->getIterable('[options]') as $option) {
                    $entries[] = new SuggestionEntry(
                        $option->getString('[text]'),
                        $option->getFloat('[score]'),
                        $option->getInt('[freq]')
                    );
                }
                $ret[] = new Suggestion($name, $entries);
            }
        }

        return $ret;
    }

    /**
     * @return Aggregation[]
     */
    protected function transformAggregations(TypeArray $response): array
    {
        // Note: only does bucket aggregations!
        $ret = [];

        // We have to convert back to toArray(), as we don't have an option to iterate over root (yet).
        foreach ($response->toArray() as $name => $aggregation) {
            // This element is not an array, so it cannot be an aggregation (most likely a 'doc_count')
            if (! is_array($aggregation)) {
                continue;
            }
            // Convert the aggregation to a TypeArray, so we can use the helper methods
            $aggregation = new TypeArray($aggregation);

            // Check if we have buckets, if not, we might have a nested aggregation
            if (! $aggregation->exists('[buckets]')) {
                // No buckets, we might need to go deeper as this is a nested aggregation
                $ret = array_merge($ret, $this->transformAggregations($aggregation));
                continue;
            }

            $ret[] = $this->aggregationMapper->map(
                strval($name),
                $aggregation->getIterable('[buckets]')
            );
        }

        return $ret;
    }

    /**
     * @param string[] $inquiryIds
     *
     * @return InquiryDescription[]
     */
    private function getInquiryDescriptions(array $inquiryIds): array
    {
        if (count($inquiryIds) === 0) {
            return [];
        }

        return array_map(
            static fn (Inquiry $inquiry): InquiryDescription => InquiryDescription::fromEntity($inquiry),
            $this->doctrine->getRepository(Inquiry::class)->findBy(['id' => $inquiryIds])
        );
    }

    private function getFilterDetails(Config $config): FilterDetails
    {
        Assert::keyExists($config->facetInputs, FacetKey::DOSSIER_NR->name);

        /** @var StringValuesFacetInput $facetInput */
        $facetInput = $config->facetInputs[FacetKey::DOSSIER_NR->name];

        $inputClass = FacetKey::DOSSIER_NR->getInputClass();
        Assert::isInstanceOf($facetInput, $inputClass);

        return new FilterDetails(
            $this->getInquiryDescriptions($config->dossierInquiries),
            $this->getInquiryDescriptions($config->documentInquiries),
            $facetInput->getStringValues(),
        );
    }

    private function displayWithoutDateMessage(Config $config, ?int $documentCountWithoutDate): bool
    {
        Assert::keyExists($config->facetInputs, FacetKey::DATE->name);

        /** @var DateFacetInput $facetInput */
        $facetInput = $config->facetInputs[FacetKey::DATE->name];

        $inputClass = FacetKey::DATE->getInputClass();
        Assert::isInstanceOf($facetInput, $inputClass);

        if ($facetInput->isWithoutDate()) {
            return false;
        }

        if ($facetInput->hasAnyPeriodFilterDates()) {
            return ($documentCountWithoutDate ?? 0) > 0;
        }

        return false;
    }
}
