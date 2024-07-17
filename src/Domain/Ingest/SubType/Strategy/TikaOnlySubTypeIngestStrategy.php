<?php

declare(strict_types=1);

namespace App\Domain\Ingest\SubType\Strategy;

use App\Domain\Ingest\IngestOptions;
use App\Domain\Ingest\SubType\SubTypeIngestStrategyInterface;
use App\Domain\Ingest\TikaOnly\IngestTikaOnlyCommand;
use App\Entity\EntityWithFileInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class TikaOnlySubTypeIngestStrategy implements SubTypeIngestStrategyInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(EntityWithFileInfo $entity, IngestOptions $options): void
    {
        $this->logger->info('Dispatching tika-only ingest for entity', [
            'id' => $entity->getId(),
            'class' => $entity::class,
        ]);

        $this->bus->dispatch(
            IngestTikaOnlyCommand::forEntity($entity, $options->forceRefresh())
        );
    }

    public function canHandle(EntityWithFileInfo $entity): bool
    {
        return $entity->getFileInfo()->isUploaded();
    }

    public static function getDefaultPriority(): int
    {
        return -10;
    }
}
