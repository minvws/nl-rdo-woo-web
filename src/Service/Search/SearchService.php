<?php

declare(strict_types=1);

namespace App\Service\Search;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchParametersFactory;
use App\Service\Elastic\ElasticClientInterface;
use App\Service\Search\Object\ObjectHandler;
use App\Service\Search\Query\QueryGenerator;
use App\Service\Search\Result\Result;
use App\Service\Search\Result\ResultTransformer;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Psr\Log\LoggerInterface;

class SearchService
{
    public function __construct(
        protected ElasticClientInterface $elastic,
        protected LoggerInterface $logger,
        protected QueryGenerator $queryGenerator,
        protected ObjectHandler $objectHandler,
        protected ResultTransformer $resultTransformer,
        protected SearchParametersFactory $searchParametersFactory,
    ) {
    }

    public function searchFacets(SearchParameters $searchParameters): Result
    {
        $query = $this->queryGenerator->createFacetsQuery($searchParameters);

        return $this->doSearch($query->build(), $searchParameters);
    }

    public function search(SearchParameters $searchParameters): Result
    {
        $query = $this->queryGenerator->createQuery($searchParameters);

        return $this->doSearch($query->build(), $searchParameters);
    }

    public function isIngested(Document $document): bool
    {
        return $this->objectHandler->isIngested($document);
    }

    public function getPageContent(Document $document, int $pageNr): string
    {
        return $this->objectHandler->getPageContent($document, $pageNr);
    }

    public function retrieveExtendedFacets(): Result
    {
        $searchParameters = $this->searchParametersFactory->createDefault();
        $query = $this->queryGenerator->createExtendedFacetsQuery($searchParameters);

        return $this->doSearch($query->build(), $searchParameters);
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function doSearch(array $query, SearchParameters $searchParameters): Result
    {
        try {
            /** @var Elasticsearch $response */
            $response = $this->elastic->search($query);
        } catch (\Exception $e) {
            $this->logger->error('ElasticSearch error', [
                'exception' => $e->getMessage(),
                'query' => $query,
            ]);

            return Result::create()
                ->setFailed(true)
                ->setMessage($e->getMessage())
                ->setQuery($query);
        }

        $result = $this->resultTransformer->transform($query, $searchParameters, $response);

        return $result;
    }
}
