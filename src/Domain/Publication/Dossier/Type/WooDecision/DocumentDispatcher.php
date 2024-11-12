<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\RemoveDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\ReplaceDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\WithDrawDocumentCommand;
use App\Entity\Document;
use App\Entity\WithdrawReason;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class DocumentDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatchProcessDocumentCommand(
        Uuid $dossierUuid,
        string $remotePath,
        string $originalFilename,
        bool $chunked = false,
        string $chunkUuid = '',
        int $chunkCount = 0,
    ): void {
        $this->messageBus->dispatch(
            new ProcessDocumentCommand(
                $dossierUuid,
                $remotePath,
                $originalFilename,
                $chunked,
                $chunkUuid,
                $chunkCount,
            ),
        );
    }

    public function dispatchRemoveDocumentCommand(Uuid $dossierId, Uuid $documentId): void
    {
        $this->messageBus->dispatch(
            new RemoveDocumentCommand($dossierId, $documentId),
        );
    }

    public function dispatchReplaceDocumentCommand(
        Uuid $dossierId,
        Uuid $documentId,
        string $remotePath,
        string $originalFilename,
        bool $chunked = false,
        string $chunkUuid = '',
        int $chunkCount = 0,
    ): void {
        $this->messageBus->dispatch(
            new ReplaceDocumentCommand(
                $dossierId,
                $documentId,
                $remotePath,
                $originalFilename,
                $chunked,
                $chunkUuid,
                $chunkCount,
            ),
        );
    }

    public function dispatchWithdrawDocumentCommand(
        WooDecision $wooDecision,
        Document $document,
        WithdrawReason $reason,
        string $explanation,
    ): void {
        $this->messageBus->dispatch(
            new WithDrawDocumentCommand($wooDecision, $document, $reason, $explanation),
        );
    }
}
