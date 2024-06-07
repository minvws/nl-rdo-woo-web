<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Handler;

use App\Domain\Publication\Dossier\Command\CreateDossierCommand;
use App\Domain\Publication\Dossier\Event\DossierCreatedEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class CreateDossierHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private DossierService $dossierService,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(CreateDossierCommand $command): void
    {
        $this->dossierWorkflowManager->applyTransition($command->dossier, DossierStatusTransition::UPDATE_DETAILS);

        $this->dossierService->validateCompletion($command->dossier);

        $this->messageBus->dispatch(
            DossierCreatedEvent::forDossier($command->dossier),
        );
    }
}
