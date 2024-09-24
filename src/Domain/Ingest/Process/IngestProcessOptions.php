<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process;

class IngestProcessOptions
{
    protected bool $forceRefresh = false;            // Can extractors use cache (false) or should they extract data from the original file (true)?

    public function forceRefresh(): bool
    {
        return $this->forceRefresh;
    }

    public function setForceRefresh(bool $forceRefresh): void
    {
        $this->forceRefresh = $forceRefresh;
    }
}
