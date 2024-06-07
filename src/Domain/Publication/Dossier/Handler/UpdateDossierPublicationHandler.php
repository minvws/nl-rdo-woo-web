<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Handler;

use App\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use App\Domain\Publication\Dossier\DossierPublisher;
use App\Domain\Publication\Dossier\Event\DossierUpdatedEvent;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class UpdateDossierPublicationHandler
{
    public function __construct(
        private DossierService $dossierService,
        private MessageBusInterface $messageBus,
        private DossierPublisher $dossierPublisher,
    ) {
    }

    public function __invoke(UpdateDossierPublicationCommand $command): void
    {
        $dossier = $command->dossier;

        $this->dossierService->validateCompletion($dossier, false);

        $canPublish = $this->dossierPublisher->canPublish($dossier);
        $canSchedule = $this->dossierPublisher->canSchedulePublication($dossier);
        if (! $canSchedule && ! $canPublish) {
            throw DossierWorkflowException::forCannotUpdatePublication($dossier);
        }

        $this->dossierService->updateHistory($dossier);
        $this->dossierService->validateCompletion($dossier);

        if ($canPublish) {
            $this->dossierPublisher->publish($dossier);
        } else {
            $this->dossierPublisher->schedulePublication($dossier);
        }

        $this->messageBus->dispatch(
            DossierUpdatedEvent::forDossier($dossier),
        );
    }
}
