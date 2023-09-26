<?php

declare(strict_types=1);

namespace App\Service\DossierWorkflow\Step;

use App\Entity\Dossier;
use App\Service\DossierWorkflow\StepName;

class DocumentsStep implements StepInterface
{
    public function isCompleted(Dossier $dossier): bool
    {
        return ! $dossier->needsInventoryAndDocuments() || $this->dossierHasAllExpectedUploads($dossier);
    }

    public function getConceptEditPath(): string
    {
        return 'app_admin_dossier_concept_documents';
    }

    public function getEditPath(): string
    {
        return 'app_admin_documents';
    }

    public function getStepName(): StepName
    {
        return StepName::DOCUMENTS;
    }

    private function dossierHasAllExpectedUploads(Dossier $dossier): bool
    {
        return $dossier->getInventory()?->getFileInfo()->isUploaded()
            && $dossier->getUploadStatus()->isComplete();
    }
}
