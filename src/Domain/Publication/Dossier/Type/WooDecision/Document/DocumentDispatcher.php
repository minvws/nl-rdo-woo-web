<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Command\RemoveDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Command\WithDrawDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Event\AllDocumentsWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Event\DocumentRepublishedEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Event\DocumentWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class DocumentDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatchRemoveDocumentCommand(Uuid $dossierId, Uuid $documentId): void
    {
        $this->messageBus->dispatch(
            new RemoveDocumentCommand($dossierId, $documentId),
        );
    }

    public function dispatchWithdrawDocumentCommand(
        WooDecision $wooDecision,
        Document $document,
        DocumentWithdrawReason $reason,
        string $explanation,
    ): void {
        $this->messageBus->dispatch(
            new WithDrawDocumentCommand(
                $wooDecision->getId(),
                $document->getId(),
                $reason,
                $explanation,
            ),
        );
    }

    public function dispatchDocumentWithdrawnEvent(
        Document $document,
        DocumentWithdrawReason $reason,
        string $explanation,
        bool $bulkAction,
    ): void {
        $this->messageBus->dispatch(
            new DocumentWithDrawnEvent($document, $reason, $explanation, $bulkAction)
        );
    }

    public function dispatchAllDocumentsWithdrawnEvent(
        WooDecision $wooDecision,
        DocumentWithdrawReason $reason,
        string $explanation,
    ): void {
        $this->messageBus->dispatch(
            new AllDocumentsWithDrawnEvent($wooDecision, $reason, $explanation)
        );
    }

    public function dispatchDocumentRepublishedEvent(Document $document): void
    {
        $this->messageBus->dispatch(
            new DocumentRepublishedEvent($document)
        );
    }
}
