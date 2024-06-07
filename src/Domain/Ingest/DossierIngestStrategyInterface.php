<?php

declare(strict_types=1);

namespace App\Domain\Ingest;

use App\Domain\Publication\Dossier\AbstractDossier;

interface DossierIngestStrategyInterface
{
    public function ingest(AbstractDossier $dossier, bool $refresh): void;
}