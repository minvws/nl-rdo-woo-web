<?php

declare(strict_types=1);

namespace Shared\Service\Search;

use Elastic\Elasticsearch\Response\Elasticsearch;
use Erichard\ElasticQueryBuilder\QueryBuilder;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Domain\Search\Query\SearchParametersFactory;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Service\Search\Object\ObjectHandler;
use Shared\Service\Search\Query\Definition\QueryDefinitionInterface;
use Shared\Service\Search\Result\Result;
use Shared\Service\Search\Result\ResultTransformer;

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
