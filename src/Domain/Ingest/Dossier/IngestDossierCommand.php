<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Dossier;

use Symfony\Component\Uid\Uuid;

readonly class IngestDossierCommand
{
    public function __construct(
        private Uuid $uuid,
        private bool $refresh = false
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
