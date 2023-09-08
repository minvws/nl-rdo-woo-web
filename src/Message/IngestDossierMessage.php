<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Uid\Uuid;

class IngestDossierMessage
{
    public function __construct(
        private readonly Uuid $uuid,
        private readonly bool $refresh = false
    ) {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getRefresh(): bool
    {
        return $this->refresh;
    }
}
