<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Uid\Uuid;

class IngestMetadataOnlyMessage
{
    public function __construct(
        private readonly Uuid $uuid,
        private readonly bool $forceRefresh = false
    ) {
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
