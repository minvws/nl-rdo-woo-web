<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class RejectProductionReportUpdateCommand
{
    public function __construct(
        public WooDecision $dossier,
    ) {
    }
}
