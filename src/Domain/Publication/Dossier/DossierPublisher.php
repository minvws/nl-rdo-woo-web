<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier;

use Shared\Domain\Publication\Dossier\Event\DossierPublishedEvent;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class DossierPublisher
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function canPublish(AbstractDossier $dossier): bool
    {
        return $this->dossierWorkflowManager->isTransitionAllowed($dossier, DossierStatusTransition::PUBLISH);
    }

    public function publish(AbstractDossier $dossier): void
    {
        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::PUBLISH);

        $this->messageBus->dispatch(
            DossierPublishedEvent::forDossier($dossier),
        );
    }

    public function canPublishAsPreview(AbstractDossier $dossier): bool
    {
        return $this->dossierWorkflowManager->isTransitionAllowed($dossier, DossierStatusTransition::PUBLISH_AS_PREVIEW);
    }

    public function publishAsPreview(AbstractDossier $dossier): void
    {
        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::PUBLISH_AS_PREVIEW);
    }

    public function canSchedulePublication(AbstractDossier $dossier): bool
    {
        return $this->dossierWorkflowManager->isTransitionAllowed($dossier, DossierStatusTransition::SCHEDULE_PUBLISH);
    }

    public function schedulePublication(AbstractDossier $dossier): void
    {
        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::SCHEDULE_PUBLISH);
    }
}
