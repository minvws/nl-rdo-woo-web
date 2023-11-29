<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Uid\Uuid;

class IngestPdfMessage
{
    protected Uuid $uuid;
    protected bool $forceRefresh;

    public function __construct(Uuid $uuid, bool $forceRefresh)
    {
        $this->uuid = $uuid;
        $this->forceRefresh = $forceRefresh;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getForceRefresh(): bool
    {
        return $this->forceRefresh;
    }
}
