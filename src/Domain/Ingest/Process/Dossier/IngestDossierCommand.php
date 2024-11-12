<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\Dossier;

use Symfony\Component\Uid\Uuid;

readonly class IngestDossierCommand
{
    public function __construct(
        public Uuid $uuid,
        public bool $refresh,
    ) {
    }
}
