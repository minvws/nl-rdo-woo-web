<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload\EventHandler;

use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Event\DocumentFileSetProcessedEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

readonly class DocumentFileSetEventHandler
{
    public function __construct(
        private WooDecisionRepository $repository,
        private BatchDownloadService $batchDownloadService,
    ) {
    }

    #[AsMessageHandler]
    public function handleDocumentFileSetProcessed(DocumentFileSetProcessedEvent $event): void
    {
        $dossier = $this->repository->findOne($event->dossierId);

        if (! $dossier->getStatus()->isPubliclyAvailable()) {
            return;
        }

        $this->batchDownloadService->refresh(
            BatchDownloadScope::forWooDecision($dossier),
        );
    }
}
