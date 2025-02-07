<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;

class ProductionReportStatus
{
    public function __construct(
        private readonly WooDecision $dossier,
    ) {
    }

    public function needsUpload(): bool
    {
        return $this->dossier->getProductionReport() === null;
    }

    public function isReadyForDocumentUpload(): bool
    {
        $run = $this->dossier->getProcessRun();

        return $run && $run->isFinal() && $this->dossier->getProductionReport() !== null;
    }

    public function needsUpdate(): bool
    {
        $run = $this->dossier->getProcessRun();

        return $run && $run->isNotFinal() && ! $run->needsConfirmation();
    }

    public function isQueued(): bool
    {
        $run = $this->dossier->getProcessRun();

        return $run && ($run->isPending() || $run->isConfirmed());
    }

    public function isRunning(): bool
    {
        $run = $this->dossier->getProcessRun();

        return $run && ($run->isComparing() || $run->isUpdating());
    }

    public function isComparing(): bool
    {
        $run = $this->dossier->getProcessRun();

        return $run && $run->isComparing();
    }

    public function isUpdating(): bool
    {
        $run = $this->dossier->getProcessRun();

        return $run && $run->isUpdating();
    }

    public function needsConfirmation(): bool
    {
        $run = $this->dossier->getProcessRun();

        return $run && $run->needsConfirmation();
    }

    /**
     * @return array<string,int>
     */
    public function getChangeset(): array
    {
        $changeset = $this->dossier->getProcessRun()?->getChangeset();

        return $changeset ? $changeset->getCounts() : [];
    }

    public function hasErrors(): bool
    {
        if ($this->dossier->getProcessRun()?->isRejected()) {
            return false;
        }

        return $this->dossier->getProcessRun()?->hasErrors() ?? false;
    }
}
