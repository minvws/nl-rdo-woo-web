<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\SubType\Strategy;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngestStrategyInterface;
use App\Entity\EntityWithFileInfo;
use Psr\Log\LoggerInterface;

readonly class PdfSubTypeIngestStrategy implements SubTypeIngestStrategyInterface
{
    public function __construct(
        private IngestDispatcher $ingestDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(EntityWithFileInfo $entity, IngestProcessOptions $options): void
    {
        $this->logger->info('Dispatching ingest for PDF entity', [
            'id' => $entity->getId(),
            'class' => $entity::class,
        ]);

        $this->ingestDispatcher->dispatchIngestPdfCommand($entity, $options->forceRefresh());
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
