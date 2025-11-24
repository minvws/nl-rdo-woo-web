<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\SubType\Strategy;

use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Ingest\Process\IngestProcessOptions;
use Shared\Domain\Ingest\Process\SubType\SubTypeIngestStrategyInterface;
use Shared\Domain\Publication\EntityWithFileInfo;

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
        if ($entity->getFileInfo()->getMimetype() !== 'application/pdf') {
            return false;
        }

        return $entity->getFileInfo()->isPaginatable();
    }
}
