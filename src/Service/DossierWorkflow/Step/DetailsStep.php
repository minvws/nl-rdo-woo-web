<?php

declare(strict_types=1);

namespace App\Service\DossierWorkflow\Step;

use App\Entity\Dossier;
use App\Service\DossierWorkflow\StepName;

class DetailsStep implements StepInterface
{
    public function isCompleted(Dossier $dossier): bool
    {
        return ! empty($dossier->getTitle())
            && ! $dossier->getDepartments()->isEmpty()
            && ! empty($dossier->getDocumentPrefix())
            && ! empty($dossier->getPublicationReason());
    }

    public function getConceptEditPath(): string
    {
        return 'app_admin_dossier_concept_details';
    }

    public function getEditPath(): string
    {
        return 'app_admin_dossier_edit_details';
    }

    public function getStepName(): StepName
    {
        return StepName::DETAILS;
    }
}
