<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Handler;

use App\Domain\Publication\Dossier\Command\UpdateDossierContentCommand;
use App\Domain\Publication\Dossier\Event\DossierUpdatedEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class UpdateDossierContentHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private DossierService $dossierService,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(UpdateDossierContentCommand $command): void
    {
        $this->dossierWorkflowManager->applyTransition($command->dossier, DossierStatusTransition::UPDATE_CONTENT);

        $this->dossierService->validateCompletion($command->dossier);

        $this->messageBus->dispatch(
            DossierUpdatedEvent::forDossier($command->dossier),
        );
    }
}
