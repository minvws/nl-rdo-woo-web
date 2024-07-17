<?php

declare(strict_types=1);

namespace App\Domain\Ingest\SubType\Strategy;

use App\Domain\Ingest\IngestOptions;
use App\Domain\Ingest\MetadataOnly\IngestMetadataOnlyCommand;
use App\Domain\Ingest\SubType\SubTypeIngestStrategyInterface;
use App\Entity\EntityWithFileInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class MetadataOnlySubTypeIngestStrategy implements SubTypeIngestStrategyInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(EntityWithFileInfo $entity, IngestOptions $options): void
    {
        $this->logger->info('Dispatching ingest for metadata-only entity', [
            'id' => $entity->getId(),
            'class' => $entity::class,
        ]);

        // Set refresh to true so any existing metadata or pages from an older version are removed.
        $this->bus->dispatch(
            IngestMetadataOnlyCommand::forEntity($entity, true)
        );
    }

    public function canHandle(EntityWithFileInfo $entity): bool
    {
        return $entity->getFileInfo()->isUploaded() === false;
    }
}
