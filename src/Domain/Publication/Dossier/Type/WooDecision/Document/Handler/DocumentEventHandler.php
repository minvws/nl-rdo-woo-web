<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Handler;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event\AllDocumentsWithDrawnEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event\DocumentRepublishedEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event\DocumentWithDrawnEvent;
use Shared\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

readonly class DocumentEventHandler
{
    public function __construct(
        private DossierService $dossierService,
    ) {
    }

    #[AsMessageHandler]
    public function handleDocumentWithdrawn(DocumentWithDrawnEvent $event): void
    {
        if ($event->isBulkAction()) {
            // For a bulk action do not validate for each individual document, but via the AllDocumentsWithDrawnEvent
            return;
        }

        foreach ($event->document->getDossiers() as $dossier) {
            $this->dossierService->validateCompletion($dossier);
        }
    }

    #[AsMessageHandler]
    public function handleAllDocumentsWithdrawn(AllDocumentsWithDrawnEvent $event): void
    {
        $this->dossierService->validateCompletion($event->dossier);
    }

    #[AsMessageHandler]
    public function handleDocumentRepublished(DocumentRepublishedEvent $event): void
    {
        foreach ($event->document->getDossiers() as $dossier) {
            $this->dossierService->validateCompletion($dossier);
        }
    }
}
