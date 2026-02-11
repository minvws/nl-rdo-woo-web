<?php

declare(strict_types=1);

namespace Shared\Service\Search\Result;

use Elastic\Elasticsearch\Response\Elasticsearch;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use MinVWS\TypeArray\TypeArray;
use Psr\Log\LoggerInterface;
use Shared\Domain\Search\Query\Facet\Input\DateFacetInput;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Domain\Search\Result\ResultFactory;
use Shared\Service\Search\Model\Aggregation;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Search\Model\Suggestion;
use Shared\Service\Search\Model\SuggestionEntry;
use Shared\Service\Search\Query\Sort\ViewModel\SortItemViewFactory;

use function array_filter;
use function array_merge;
use function is_array;
use function strval;

readonly class ResultTransformer
{
    public function __construct(
        private LoggerInterface $logger,
        private PaginatorInterface $paginator,
        private AggregationMapper $aggregationMapper,
        private ResultFactory $resultFactory,
        private SortItemViewFactory $sortItemViewFactory,
    ) {
    }

    /**
     * @param array<string,mixed> $query
     */
    public function transform(
        array $query,
        SearchParameters $searchParameters,
        ?Elasticsearch $response,
    ): Result {
        if (! $response) {
            $this->logger->error('ElasticSearch did not return a response', [
                'query' => $query,
            ]);

            return Result::create()
                ->setFailed(true)
                ->setMessage('No response from ElasticSearch')
                ->setQuery($query);
        }

        // Populate result object with search and search parameters data
        $result = $this->transformResults($searchParameters, $response);
        $result->setQuery($query);
        $result->setType($searchParameters->searchType->value);

        // Copy limit/offset to the result, so we can create pagination
        $result->setOffset($searchParameters->offset);
        $result->setLimit($searchParameters->limit);

        if ($searchParameters->pagination && $result->getLimit() > 0) {
            /** @var PaginationInterface<int,AbstractPagination> $pagination */
            $pagination = $this->paginator->paginate(
                target: $result,
                page: $searchParameters->limit > 0 ? ($searchParameters->offset / $searchParameters->limit + 1) : 1,
                limit: $searchParameters->limit
            );
            $result->setPagination($pagination);
        }

        $result->setSortItems($this->sortItemViewFactory->make($searchParameters));
        $result->setSearchParameters($searchParameters);

        return $result;
    }

    /**
     * Transforms the elasticsearch result array into a result object.
     */
    protected function transformResults(
        SearchParameters $searchParameters,
        Elasticsearch $response,
    ): Result {
        $typedResponse = new TypeArray($response->asArray());
        $result = new Result();

        $result->setTimeTaken($typedResponse->getInt('[took]', 0));
        if ($typedResponse->getBool('[timed_out]')) {
            $result->setFailed(true);
            $result->setMessage('ElasticSearch timed out');

            return $result;
        }

        $result->setResultCount($typedResponse->getInt('[hits][total][value]', 0));

        if ($searchParameters->aggregations === true) {
            $result->setDossierCount($typedResponse->getInt('[aggregations][unique_dossiers][value]', 0));

            $documentCountWithoutDate = $typedResponse->getIntOrNull('[aggregations][all][facet-base-filter][date_filter][doc_count]')
                ?? $typedResponse->getIntOrNull('[aggregations][all][facet-base-filter][facet-filter-date][date_filter][doc_count]');
            $result->setDocumentCountWithoutDate($documentCountWithoutDate);
            $result->setDisplayWithoutDateMessage($this->displayWithoutDateMessage($searchParameters, $documentCountWithoutDate));
        }

        $suggestions = $this->transformSuggestions($typedResponse);
        if ($suggestions) {
            $result->setSuggestions($suggestions);
        }

        if ($searchParameters->aggregations) {
            $aggregations = $this->transformAggregations(
                $searchParameters,
                $typedResponse->getTypeArray('[aggregations]'),
            );
            if ($aggregations) {
                $result->setAggregations($aggregations);
            }
        }

        // Add all found hits and their documents
        $entries = [];
        foreach ($typedResponse->getIterable('[hits][hits]') as $hit) {
            $entries[] = $this->resultFactory->map($hit, $searchParameters->mode);
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
    protected function transformAggregations(SearchParameters $searchParameters, TypeArray $response): array
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
                $ret = array_merge(
                    $ret,
                    $this->transformAggregations($searchParameters, $aggregation),
                );

                continue;
            }

            $ret[] = $this->aggregationMapper->map(
                strval($name),
                $aggregation->getIterable('[buckets]'),
                $searchParameters,
            );
        }

        return $ret;
    }

    private function displayWithoutDateMessage(SearchParameters $searchParameters, ?int $documentCountWithoutDate): bool
    {
        /** @var DateFacetInput $facetInput */
        $facetInput = $searchParameters->facetInputs->getByFacetKey(FacetKey::DATE);

        if ($facetInput->isWithoutDate()) {
            return false;
        }

        if ($facetInput->hasAnyPeriodFilterDates()) {
            return ($documentCountWithoutDate ?? 0) > 0;
        }

        return false;
    }
}
