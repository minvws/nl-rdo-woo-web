<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class RejectProductionReportUpdateCommand
{
    public function __construct(
        public WooDecision $dossier,
    ) {
    }
}
