<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\SubType\Strategy;

use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Ingest\Process\IngestProcessOptions;
use Shared\Domain\Ingest\Process\SubType\SubTypeIngestStrategyInterface;
use Shared\Domain\Publication\EntityWithFileInfo;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: -10)]
readonly class TikaOnlySubTypeIngestStrategy implements SubTypeIngestStrategyInterface
{
    public function __construct(
        private IngestDispatcher $ingestDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(EntityWithFileInfo $entity, IngestProcessOptions $options): void
    {
        $this->logger->info('Dispatching tika-only ingest for entity', [
            'id' => $entity->getId(),
            'class' => $entity::class,
        ]);

        $this->ingestDispatcher->dispatchIngestTikaOnlyCommand($entity, $options->forceRefresh());
    }

    public function canHandle(EntityWithFileInfo $entity): bool
    {
        return $entity->getFileInfo()->isUploaded();
    }
}
