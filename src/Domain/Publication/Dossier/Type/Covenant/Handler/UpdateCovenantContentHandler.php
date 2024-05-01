<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Handler;

use App\Domain\Publication\Dossier\Type\Covenant\Command\UpdateCovenantContentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\Event\CovenantUpdatedEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class UpdateCovenantContentHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private DossierService $dossierService,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(UpdateCovenantContentCommand $command): void
    {
        $covenant = $command->covenant;

        $this->dossierWorkflowManager->applyTransition($covenant, DossierStatusTransition::UPDATE_CONTENT);

        $this->dossierService->updateHistory($covenant);
        $this->dossierService->validateCompletion($covenant);

        $this->messageBus->dispatch(
            new CovenantUpdatedEvent($covenant->getId()),
        );
    }
}
