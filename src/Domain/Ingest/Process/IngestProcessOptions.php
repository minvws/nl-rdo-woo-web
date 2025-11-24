<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process;

class IngestProcessOptions
{
    /**
     * @param bool $forceRefresh Can extractors use cache (false) or should they extract data from the original file (true)?
     */
    public function __construct(protected bool $forceRefresh = false)
    {
    }

    public function forceRefresh(): bool
    {
        return $this->forceRefresh;
    }

    public function setForceRefresh(bool $forceRefresh): void
    {
        $this->forceRefresh = $forceRefresh;
    }
}
