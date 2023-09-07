<?php

declare(strict_types=1);

namespace App\Service\Search;

use App\Entity\Document;
use App\Service\Elastic\ElasticClientInterface;
use App\Service\Search\Model\Config;
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
        protected ResultTransformer $resultTransformer
    ) {
    }

    public function searchFacets(Config $config): Result
    {
        $query = $this->queryGenerator->createFacetsQuery($config);

        return $this->doSearch($query->build(), $config);
    }

    public function search(Config $config): Result
    {
        $query = $this->queryGenerator->createQuery($config);

        return $this->doSearch($query->build(), $config);
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
        $config = new Config(limit: 0);
        $query = $this->queryGenerator->createExtendedFacetsQuery($config);

        return $this->doSearch($query->build(), $config);
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function doSearch(array $query, Config $config): Result
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

        $result = $this->resultTransformer->transform($query, $config, $response);

        return $result;
    }
}
