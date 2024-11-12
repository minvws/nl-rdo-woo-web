<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Command;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

abstract readonly class AbstractWooDecisionCommand
{
    public function __construct(
        public WooDecision $dossier,
    ) {
    }
}
