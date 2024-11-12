<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

class InventoryStatus
{
    public function __construct(
        private readonly WooDecision $dossier,
    ) {
    }

    public function isUploaded(): bool
    {
        $run = $this->dossier->getProcessRun();

        return ($run && $run->isFinished()) && $this->dossier->getProductionReport() !== null;
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

    public function canUpload(): bool
    {
        $run = $this->dossier->getProcessRun();

        if (! $this->dossier->getStatus()->isConcept()) {
            return true;
        }

        return ($run && $run->isFailed()) || $this->dossier->getProductionReport() === null;
    }

    public function hasErrors(): bool
    {
        return $this->dossier->getProcessRun()?->hasErrors() ?? false;
    }
}
