<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Steps;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Step\StepDefinitionInterface;
use App\Domain\Publication\Dossier\Step\StepException;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class DocumentsStepDefinition implements StepDefinitionInterface
{
    public function getName(): StepName
    {
        return StepName::DOCUMENTS;
    }

    public function isCompleted(AbstractDossier $dossier): bool
    {
        if (! $dossier instanceof WooDecision) {
            throw StepException::forIncompatibleDossierInstance($this, $dossier);
        }

        return ! $dossier->needsInventoryAndDocuments() || $this->dossierHasAllExpectedUploads($dossier);
    }

    public function getConceptEditRouteName(): string
    {
        return 'app_admin_dossier_woodecision_documents_concept';
    }

    public function getEditRouteName(): string
    {
        return 'app_admin_dossier_woodecision_documents_edit';
    }

    private function dossierHasAllExpectedUploads(WooDecision $dossier): bool
    {
        return $dossier->getRawInventory()?->getFileInfo()->isUploaded()
            && $dossier->getUploadStatus()->isComplete();
    }
}
