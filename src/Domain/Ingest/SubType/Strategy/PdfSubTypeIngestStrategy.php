<?php

declare(strict_types=1);

namespace App\Domain\Ingest\SubType\Strategy;

use App\Domain\Ingest\IngestOptions;
use App\Domain\Ingest\Pdf\IngestPdfCommand;
use App\Domain\Ingest\SubType\SubTypeIngestStrategyInterface;
use App\Entity\EntityWithFileInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class PdfSubTypeIngestStrategy implements SubTypeIngestStrategyInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(EntityWithFileInfo $entity, IngestOptions $options): void
    {
        $this->logger->info('Dispatching ingest for PDF entity', [
            'id' => $entity->getId(),
            'class' => $entity::class,
        ]);

        $this->bus->dispatch(
            IngestPdfCommand::forEntity($entity, $options->forceRefresh())
        );
    }

    public function canHandle(EntityWithFileInfo $entity): bool
    {
        // Should be removed after we support other types
        if ($entity->getFileInfo()->getMimetype() !== 'application/pdf') {
            return false;
        }

        return $entity->getFileInfo()->isPaginatable();
    }
}
