<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\ElasticConfig;
use App\Message\IngestDossiersMessage;
use App\Message\InitializeElasticRolloverMessage;
use App\Service\Elastic\IndexService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Initialize the elasticsearch rollover and does a dossier ingestion of all dossiers.
 */
#[AsMessageHandler]
class InitializeElasticRolloverHandler
{
    public function __construct(
        protected IndexService $indexService,
        protected LoggerInterface $logger,
        protected MessageBusInterface $bus,
    ) {
    }

    public function __invoke(InitializeElasticRolloverMessage $message): void
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

            $ingestDossiers = new IngestDossiersMessage();
            $this->bus->dispatch($ingestDossiers);
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize the elasticsearch rollover', [
                'index_name' => $message->indexName,
                'mapping_version' => $message->mappingVersion,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
