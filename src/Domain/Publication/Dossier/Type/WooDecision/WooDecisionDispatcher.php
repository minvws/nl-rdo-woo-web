<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\GenerateInquiryInventoryCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\RemoveInventoryAndDocumentsCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\UpdateDecisionCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\UpdateInquiryLinksCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\WithDrawAllDocumentsCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
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
        WithdrawReason $reason,
        string $explanation,
    ): void {
        $this->messageBus->dispatch(
            new WithDrawAllDocumentsCommand($wooDecision, $reason, $explanation),
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
            new UpdateInquiryLinksCommand($id, $caseNr, $docIdsToAdd, $docIdsToDelete, $dossierIdsToAdd),
        );
    }
}
