<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Handler;

use Shared\Domain\Publication\Dossier\Command\UpdateDossierDetailsCommand;
use Shared\Domain\Publication\Dossier\Event\DossierUpdatedEvent;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class UpdateDossierDetailsHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private DossierService $dossierService,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(UpdateDossierDetailsCommand $command): void
    {
        $this->dossierWorkflowManager->applyTransition($command->dossier, DossierStatusTransition::UPDATE_DETAILS);

        $this->dossierService->validateCompletion($command->dossier);

        $this->messageBus->dispatch(
            DossierUpdatedEvent::forDossier($command->dossier),
        );
    }
}
