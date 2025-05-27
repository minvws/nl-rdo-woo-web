<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload\EventHandler;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\BatchDownloadService;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Event\AllDocumentsWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Event\DocumentWithDrawnEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

readonly class DocumentEventHandler
{
    public function __construct(
        private BatchDownloadService $batchDownloadService,
    ) {
    }

    #[AsMessageHandler]
    public function handleDocumentWithdrawn(DocumentWithDrawnEvent $event): void
    {
        if ($event->isBulkAction()) {
            // For a bulk action do not refresh for each individual document, but via the AllDocumentsWithDrawnEvent
            return;
        }

        foreach ($event->document->getDossiers() as $dossier) {
            $this->batchDownloadService->refresh(
                BatchDownloadScope::forWooDecision($dossier),
            );
        }
    }

    #[AsMessageHandler]
    public function handleAllDocumentsWithdrawn(AllDocumentsWithDrawnEvent $event): void
    {
        $this->batchDownloadService->refresh(
            BatchDownloadScope::forWooDecision($event->dossier),
        );
    }
}
