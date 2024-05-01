<?php

declare(strict_types=1);

namespace App\Domain\Ingest;

use App\Domain\Ingest\Covenant\CovenantIngester;
use App\Domain\Ingest\WooDecision\WooDecisionIngester;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class DossierIngester
{
    public function __construct(
        private CovenantIngester $covenantIngester,
        private WooDecisionIngester $wooDecisionIngester,
    ) {
    }

    public function ingest(AbstractDossier $dossier, bool $refresh = false): void
    {
        match (true) {
            $dossier instanceof WooDecision => $this->wooDecisionIngester->ingest($dossier, $refresh),
            $dossier instanceof Covenant => $this->covenantIngester->ingest($dossier, $refresh),
            default => throw IngestException::forUnsupportedDossierType($dossier->getType()),
        };
    }
}
