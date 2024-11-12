<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Event\DossierUpdatedEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\UpdateDecisionCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class UpdateDecisionHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private DossierService $dossierService,
        private MessageBusInterface $messageBus,
        private WooDecisionDispatcher $wooDecisionDispatcher,
    ) {
    }

    public function __invoke(UpdateDecisionCommand $command): void
    {
        $this->dossierWorkflowManager->applyTransition($command->dossier, DossierStatusTransition::UPDATE_DECISION);

        $this->dossierService->validateCompletion($command->dossier);

        // If the dossier no longer needs inventory and documents: remove them
        if (! $command->dossier->needsInventoryAndDocuments()) {
            $this->wooDecisionDispatcher->dispatchRemoveInventoryAndDocumentsCommand($command->dossier->getId());
        }

        $this->messageBus->dispatch(
            DossierUpdatedEvent::forDossier($command->dossier),
        );
    }
}
