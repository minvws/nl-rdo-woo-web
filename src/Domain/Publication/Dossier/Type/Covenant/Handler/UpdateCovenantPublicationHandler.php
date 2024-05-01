<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Handler;

use App\Domain\Publication\Dossier\DossierPublisher;
use App\Domain\Publication\Dossier\Type\Covenant\Command\UpdateCovenantPublicationCommand;
use App\Domain\Publication\Dossier\Type\Covenant\Event\CovenantUpdatedEvent;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class UpdateCovenantPublicationHandler
{
    public function __construct(
        private DossierService $dossierService,
        private MessageBusInterface $messageBus,
        private DossierPublisher $dossierPublisher,
    ) {
    }

    public function __invoke(UpdateCovenantPublicationCommand $command): void
    {
        $covenant = $command->covenant;

        $this->dossierService->validateCompletion($covenant, false);

        $canPublish = $this->dossierPublisher->canPublish($covenant);
        $canSchedule = $this->dossierPublisher->canSchedulePublication($covenant);
        if (! $canSchedule && ! $canPublish) {
            throw DossierWorkflowException::forCannotUpdatePublication($covenant);
        }

        $this->dossierService->updateHistory($covenant);
        $this->dossierService->validateCompletion($covenant);

        if ($canPublish) {
            $this->dossierPublisher->publish($covenant);
        } else {
            $this->dossierPublisher->schedulePublication($covenant);
        }

        $this->messageBus->dispatch(
            new CovenantUpdatedEvent($covenant->getId()),
        );
    }
}
