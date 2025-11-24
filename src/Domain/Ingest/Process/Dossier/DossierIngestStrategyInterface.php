<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\Dossier;

use Shared\Domain\Publication\Dossier\AbstractDossier;

interface DossierIngestStrategyInterface
{
    public function ingest(AbstractDossier $dossier, bool $refresh): void;
}
