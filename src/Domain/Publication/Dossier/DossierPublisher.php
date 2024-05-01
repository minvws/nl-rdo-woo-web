<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;

readonly class DossierPublisher
{
    public function __construct(private DossierWorkflowManager $dossierWorkflowManager)
    {
    }

    public function canPublish(AbstractDossier $dossier): bool
    {
        return $this->dossierWorkflowManager->isTransitionAllowed($dossier, DossierStatusTransition::PUBLISH);
    }

    public function publish(AbstractDossier $dossier): void
    {
        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::PUBLISH);
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
        return $this->dossierWorkflowManager->isTransitionAllowed($dossier, DossierStatusTransition::SCHEDULE);
    }

    public function schedulePublication(AbstractDossier $dossier): void
    {
        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::SCHEDULE);
    }
}
