<?php

declare(strict_types=1);

namespace App\Service\DossierWorkflow\Step;

use App\Entity\Dossier;
use App\Service\DossierWorkflow\StepName;

class DecisionStep implements StepInterface
{
    public function isCompleted(Dossier $dossier): bool
    {
        return ! empty($dossier->getDecision())
            && ! empty($dossier->getSummary())
            && $dossier->getDecisionDocument()?->getFileInfo()->isUploaded();
    }

    public function getConceptEditPath(): string
    {
        return 'app_admin_dossier_concept_decision';
    }

    public function getEditPath(): string
    {
        return 'app_admin_dossier_edit_decision';
    }

    public function getStepName(): StepName
    {
        return StepName::DECISION;
    }
}
