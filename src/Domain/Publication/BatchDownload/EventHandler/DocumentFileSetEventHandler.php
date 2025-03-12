<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload\EventHandler;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\BatchDownloadService;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Event\DocumentFileSetProcessedEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
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
