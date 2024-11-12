<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\WithDrawAllDocumentsCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\AllDocumentsWithDrawnEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DocumentWorkflow\DocumentWorkflowStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class WithDrawAllDocumentsHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private MessageBusInterface $messageBus,
        private DocumentDispatcher $documentDispatcher,
    ) {
    }

    public function __invoke(WithDrawAllDocumentsCommand $command): void
    {
        $this->dossierWorkflowManager->applyTransition($command->dossier, DossierStatusTransition::UPDATE_DOCUMENTS);

        foreach ($command->dossier->getDocuments() as $document) {
            $documentStatus = new DocumentWorkflowStatus($document);
            if ($documentStatus->canWithdraw()) {
                $this->documentDispatcher->dispatchWithdrawDocumentCommand(
                    $command->dossier,
                    $document,
                    $command->reason,
                    $command->explanation,
                );
            }
        }

        $this->messageBus->dispatch(
            new AllDocumentsWithDrawnEvent($command->dossier, $command->reason, $command->explanation)
        );
    }
}
