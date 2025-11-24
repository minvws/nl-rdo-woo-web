<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Decision;

use Shared\Domain\Publication\Dossier\Event\DossierUpdatedEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Service\DossierService;
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

        if (! $command->dossier->canProvideInventory()) {
            $this->wooDecisionDispatcher->dispatchRemoveInventoryAndDocumentsCommand($command->dossier->getId());
        }

        $this->messageBus->dispatch(
            DossierUpdatedEvent::forDossier($command->dossier),
        );
    }
}
