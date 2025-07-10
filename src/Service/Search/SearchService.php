<?php

declare(strict_types=1);

namespace App\Service\Search;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Search\Index\ElasticConfig;
use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchParametersFactory;
use App\Service\Elastic\ElasticClientInterface;
use App\Service\Search\Object\ObjectHandler;
use App\Service\Search\Query\Definition\QueryDefinitionInterface;
use App\Service\Search\Result\Result;
use App\Service\Search\Result\ResultTransformer;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Erichard\ElasticQueryBuilder\QueryBuilder;
use Psr\Log\LoggerInterface;

readonly class SearchService
{
    public function __construct(
        private ElasticClientInterface $elastic,
        private LoggerInterface $logger,
        private ObjectHandler $objectHandler,
        private ResultTransformer $resultTransformer,
        private SearchParametersFactory $searchParametersFactory,
    ) {
    }

    public function getResult(
        QueryDefinitionInterface $queryDefinition,
        ?SearchParameters $searchParameters = null,
    ): Result {
        if ($searchParameters === null) {
            $searchParameters = $this->searchParametersFactory->createDefault();
        }

        $queryBuilder = new QueryBuilder();
        $queryBuilder->setIndex(ElasticConfig::READ_INDEX);
        $queryBuilder->setSize($searchParameters->limit);
        $queryBuilder->setFrom($searchParameters->offset);

        $queryDefinition->configure($queryBuilder, $searchParameters);
        $query = $queryBuilder->build();

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

        return $this->resultTransformer->transform($query, $searchParameters, $response);
    }

    public function isIngested(Document $document): bool
    {
        return $this->objectHandler->isIngested($document);
    }

    public function getPageContent(Document $document, int $pageNr): string
    {
        return $this->objectHandler->getPageContent($document, $pageNr);
    }
}
