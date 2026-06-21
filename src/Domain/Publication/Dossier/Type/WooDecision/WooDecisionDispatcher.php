<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\UpdateDecisionCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Command\WithDrawAllDocumentsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Command\GenerateInquiryInventoryCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Command\UpdateInquiryLinksCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Command\RemoveDocumentsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Command\RemoveInventoryCommand;
use Shared\Domain\Upload\WooDecision\ProcessUploadedDocumentsCommand;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class WooDecisionDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatchRemoveInventoryAndDocumentsCommand(Uuid $id): void
    {
        $this->messageBus->dispatch(
            new RemoveInventoryCommand($id),
        );
        $this->messageBus->dispatch(
            new RemoveDocumentsCommand($id),
        );
    }

    public function dispatchWithdrawAllDocumentsCommand(
        WooDecision $wooDecision,
        DocumentWithdrawReason $reason,
        string $explanation,
    ): void {
        $this->messageBus->dispatch(
            new WithDrawAllDocumentsCommand(
                $wooDecision->getId(),
                $reason,
                $explanation,
            ),
        );
    }

    public function dispatchUpdateDecisionCommand(WooDecision $wooDecision): void
    {
        $this->messageBus->dispatch(
            new UpdateDecisionCommand($wooDecision),
        );
    }

    public function dispatchGenerateInquiryInventoryCommand(Uuid $id): void
    {
        $this->messageBus->dispatch(
            new GenerateInquiryInventoryCommand($id),
        );
    }

    /**
     * @param array<array-key,Uuid> $docIdsToAdd
     * @param array<array-key,Uuid> $docIdsToDelete
     * @param array<array-key,Uuid> $dossierIdsToAdd
     */
    public function dispatchUpdateInquiryLinksCommand(
        Uuid $id,
        string $inquiryNumber,
        array $docIdsToAdd,
        array $docIdsToDelete,
        array $dossierIdsToAdd,
    ): void {
        $this->messageBus->dispatch(
            new UpdateInquiryLinksCommand(
                $id,
                $inquiryNumber,
                $docIdsToAdd,
                $docIdsToDelete,
                $dossierIdsToAdd,
            ),
        );
    }

    public function dispatchProcessUploadedDocumentsCommand(Uuid $wooDecisionId, Uuid $uploadEntityId): void
    {
        $this->messageBus->dispatch(
            new ProcessUploadedDocumentsCommand($wooDecisionId, $uploadEntityId),
        );
    }
}
