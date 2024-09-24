<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\Dossier;

use App\Domain\Ingest\Process\Dossier\Strategy\DefaultDossierIngestStrategy;
use App\Domain\Ingest\Process\Dossier\Strategy\WooDecisionIngestStrategy;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class DossierIngester
{
    public function __construct(
        private DefaultDossierIngestStrategy $defaultIngester,
        private WooDecisionIngestStrategy $wooDecisionIngester,
    ) {
    }

    public function ingest(AbstractDossier $dossier, bool $refresh = false): void
    {
        match (true) {
            $dossier instanceof WooDecision => $this->wooDecisionIngester->ingest($dossier, $refresh),
            default => $this->defaultIngester->ingest($dossier, $refresh),
        };
    }
}
