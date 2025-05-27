<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Step\StepDefinition;
use App\Domain\Publication\Dossier\Step\StepException;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class DocumentsStepDefinition extends StepDefinition
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    #[\Override]
    public function isCompleted(AbstractDossier $dossier, ValidatorInterface $validator): bool
    {
        if (! $dossier instanceof WooDecision) {
            throw StepException::forIncompatibleDossierInstance($this, $dossier);
        }

        return ! $dossier->needsInventoryAndDocuments() || $this->dossierHasAllExpectedUploads($dossier);
    }

    private function dossierHasAllExpectedUploads(WooDecision $dossier): bool
    {
        return $dossier->getProductionReport()?->getFileInfo()->isUploaded()
            && $dossier->getUploadStatus()->isComplete();
    }
}
