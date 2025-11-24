<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Handler;

use Shared\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use Shared\Domain\Publication\Dossier\DossierPublisher;
use Shared\Domain\Publication\Dossier\Event\DossierUpdatedEvent;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Service\DossierService;
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

        if ($this->dossierPublisher->canPublish($dossier)) {
            $this->dossierPublisher->publish($dossier);
        } elseif ($this->dossierPublisher->canPublishAsPreview($dossier)) {
            $this->dossierPublisher->publishAsPreview($dossier);
        } elseif ($this->dossierPublisher->canSchedulePublication($dossier)) {
            $this->dossierPublisher->schedulePublication($dossier);
        } else {
            throw DossierWorkflowException::forCannotUpdatePublication($dossier);
        }

        $this->dossierService->validateCompletion($dossier);

        $this->messageBus->dispatch(
            DossierUpdatedEvent::forDossier($dossier),
        );
    }
}
