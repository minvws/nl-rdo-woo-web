<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\UpdateDecisionCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Command\WithDrawAllDocumentsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Command\GenerateInquiryInventoryCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Command\UpdateInquiryLinksCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Command\RemoveInventoryAndDocumentsCommand;
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
            new RemoveInventoryAndDocumentsCommand($id),
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
        string $caseNr,
        array $docIdsToAdd,
        array $docIdsToDelete,
        array $dossierIdsToAdd,
    ): void {
        $this->messageBus->dispatch(
            new UpdateInquiryLinksCommand(
                $id,
                $caseNr,
                $docIdsToAdd,
                $docIdsToDelete,
                $dossierIdsToAdd,
            ),
        );
    }
}
