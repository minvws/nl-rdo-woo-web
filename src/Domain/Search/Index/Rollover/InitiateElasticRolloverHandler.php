<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Rollover;

use Exception;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Shared\Domain\Search\SearchDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Initialize the Elasticsearch rollover and enqueue dossier ingestion and Subject indexing into the new write index.
 */
#[AsMessageHandler]
class InitiateElasticRolloverHandler
{
    public function __construct(
        protected ElasticIndexManager $indexService,
        protected LoggerInterface $logger,
        protected IngestDispatcher $ingestDispatcher,
        private readonly SearchDispatcher $searchDispatcher,
        private readonly ElasticConfig $elasticConfig,
    ) {
    }

    public function __invoke(InitiateElasticRolloverCommand $message): void
    {
        try {
            $this->indexService->create(
                $message->indexName,
                $message->mappingVersion,
            );

            $this->indexService->switch(
                $this->elasticConfig->writeIndex,
                srcIndex: '*',
                dstIndex: $message->indexName,
            );

            $this->ingestDispatcher->dispatchIngestAllDossiersCommand();
            $this->searchDispatcher->dispatchIndexAllSubjectsCommand();
        } catch (Exception $e) {
            $this->logger->error('Failed to initialize the elasticsearch rollover', [
                'index_name' => $message->indexName,
                'mapping_version' => $message->mappingVersion,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
