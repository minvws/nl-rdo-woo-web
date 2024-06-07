<?php

declare(strict_types=1);

namespace App\Domain\Publication\History\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Event\AllDocumentsWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DocumentWithDrawnEvent;
use App\Service\HistoryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

readonly class DocumentHistoryHandler
{
    public function __construct(
        private HistoryService $historyService,
    ) {
    }

    #[AsMessageHandler]
    public function handleDocumentWithdrawn(DocumentWithDrawnEvent $event): void
    {
        $this->historyService->addDocumentEntry(
            document: $event->document,
            key: 'document_withdraw',
            context: [
                'explanation' => '%' . $event->reason->getTranslationKey() . '%',
                'explanation_details' => $event->explanation,
            ]
        );
    }

    #[AsMessageHandler]
    public function handleAllDocumentsWithdrawn(AllDocumentsWithDrawnEvent $event): void
    {
        $this->historyService->addDossierEntry(
            dossier: $event->dossier,
            key: 'dossier_withdraw_all',
            context: [
                'explanation' => '%' . $event->reason->getTranslationKey() . '%',
                'explanation_details' => $event->explanation,
            ]
        );
    }
}
