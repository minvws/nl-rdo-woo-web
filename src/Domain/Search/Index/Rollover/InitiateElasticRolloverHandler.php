<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Rollover;

use Exception;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Initialize the elasticsearch rollover and does a dossier ingestion of all dossiers.
 */
#[AsMessageHandler]
class InitiateElasticRolloverHandler
{
    public function __construct(
        protected ElasticIndexManager $indexService,
        protected LoggerInterface $logger,
        protected IngestDispatcher $ingestDispatcher,
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
                ElasticConfig::WRITE_INDEX,
                srcIndex: '*',
                dstIndex: $message->indexName,
            );

            $this->ingestDispatcher->dispatchIngestAllDossiersCommand();
        } catch (Exception $e) {
            $this->logger->error('Failed to initialize the elasticsearch rollover', [
                'index_name' => $message->indexName,
                'mapping_version' => $message->mappingVersion,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
