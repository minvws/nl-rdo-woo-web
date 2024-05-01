<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Handler;

use App\Domain\Publication\Dossier\Type\Covenant\Command\CreateCovenantCommand;
use App\Domain\Publication\Dossier\Type\Covenant\Event\CovenantCreatedEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class CreateCovenantHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private DossierService $dossierService,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(CreateCovenantCommand $command): void
    {
        $covenant = $command->covenant;

        $this->dossierWorkflowManager->applyTransition($covenant, DossierStatusTransition::UPDATE_DETAILS);

        $this->dossierService->validateCompletion($covenant);

        $this->messageBus->dispatch(
            new CovenantCreatedEvent($covenant->getId()),
        );
    }
}
