<?php

declare(strict_types=1);

namespace App\Domain\Publication\History\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Event\AllDocumentsWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DocumentUpdateEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DocumentWithDrawnEvent;
use App\Service\HistoryService;
use App\Service\Inventory\DocumentComparator;
use App\Service\Inventory\MetadataField;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

readonly class DocumentHistoryHandler
{
    public function __construct(
        private HistoryService $historyService,
        private DocumentComparator $documentComparator,
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

    #[AsMessageHandler]
    public function handleDocumentUpdate(DocumentUpdateEvent $event): void
    {
        $changeset = $this->documentComparator->getChangeset($event->dossier, $event->document, $event->update);

        if ($changeset->isChanged(MetadataField::JUDGEMENT->value)) {
            $this->historyService->addDocumentEntry(
                document: $event->document,
                key: 'document_judgement_' . $event->update->getJudgement()->value,
                context: [
                    'old' => '%' . ($event->document->getJudgement()->value ?? '') . '%',
                    'new' => '%' . $event->update->getJudgement()->value . '%',
                ],
                flush: false
            );
        }

        if ($changeset->isChanged(MetadataField::SUSPENDED->value)) {
            $this->historyService->addDocumentEntry(
                document: $event->document,
                key: $event->update->isSuspended() ? 'document_suspended' : 'document_unsuspended',
                context: [],
                flush: false
            );
        }
    }
}
