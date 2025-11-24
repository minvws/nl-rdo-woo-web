<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Handler;

use Shared\Domain\Publication\Dossier\Command\CreateDossierCommand;
use Shared\Domain\Publication\Dossier\Event\DossierCreatedEvent;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Service\DossierService;
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
