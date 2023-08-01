<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Uid\Uuid;

class IngestPdfPageMessage
{
    protected int $pageNr;
    protected Uuid $uuid;
    protected bool $forceRefresh;

    public function __construct(Uuid $uuid, int $pageNr, bool $forceRefresh = false)
    {
        $this->pageNr = $pageNr;
        $this->uuid = $uuid;
        $this->forceRefresh = $forceRefresh;
    }

    public function getPageNr(): int
    {
        return $this->pageNr;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function forceRefresh(): bool
    {
        return $this->forceRefresh;
    }
}
