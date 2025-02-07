<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\SubType\Strategy;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngestStrategyInterface;
use App\Domain\Publication\EntityWithFileInfo;
use Psr\Log\LoggerInterface;

readonly class MetadataOnlySubTypeIngestStrategy implements SubTypeIngestStrategyInterface
{
    public function __construct(
        private IngestDispatcher $dispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(EntityWithFileInfo $entity, IngestProcessOptions $options): void
    {
        $this->logger->info('Dispatching ingest for metadata-only entity', [
            'id' => $entity->getId(),
            'class' => $entity::class,
        ]);

        // Set refresh to true so any existing metadata or pages from an older version are removed.
        $this->dispatcher->dispatchIngestMetadataOnlyCommandForEntity($entity, true);
    }

    public function canHandle(EntityWithFileInfo $entity): bool
    {
        return $entity->getFileInfo()->isUploaded() === false;
    }
}
