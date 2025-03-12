<?php

declare(strict_types=1);

namespace App\Service\Inventory\Progress;

use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;

class RunProgress
{
    private int $lastPercentage = 0;

    public function __construct(
        private readonly ProgressUpdater $progressUpdater,
        private readonly ProductionReportProcessRun $run,
        private readonly int $totalCount,
        private int $currentCount = 0,
    ) {
    }

    public function tick(): void
    {
        $this->update($this->currentCount + 1);
    }

    public function update(int $currentCount): void
    {
        if ($currentCount < 0) {
            throw new \OutOfBoundsException('Progress update must be at least 0 ');
        }

        if ($currentCount > $this->totalCount) {
            $currentCount = $this->totalCount;
        }

        $this->currentCount = $currentCount;

        // Only update when the rounded percentage actually changes, limits the update queries to max 100.
        if ($this->getPercentage() > $this->lastPercentage) {
            $this->progressUpdater->updateProgressForRun($this);
            $this->lastPercentage = $this->getPercentage();
        }
    }

    public function finish(): void
    {
        $this->update($this->totalCount);
    }

    public function getPercentage(): int
    {
        return intval(round(
            ($this->currentCount / $this->totalCount) * 100
        ));
    }

    public function getRun(): ProductionReportProcessRun
    {
        return $this->run;
    }

    public function getCurrentCount(): int
    {
        return $this->currentCount;
    }
}
