<?php

declare(strict_types=1);

namespace App\Service\DossierWorkflow\Step;

use App\Entity\Dossier;
use App\Service\DossierWorkflow\StepName;

class PublicationStep implements StepInterface
{
    public function isCompleted(Dossier $dossier): bool
    {
        return $dossier->getStatus() === Dossier::STATUS_PUBLISHED;
    }

    public function getConceptEditPath(): string
    {
        return 'app_admin_dossier_concept_publish';
    }

    public function getEditPath(): string
    {
        return 'app_admin_dossier_edit_publication';
    }

    public function getStepName(): StepName
    {
        return StepName::PUBLICATION;
    }
}
