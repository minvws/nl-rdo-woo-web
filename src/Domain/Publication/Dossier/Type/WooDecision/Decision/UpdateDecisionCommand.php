<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Decision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class UpdateDecisionCommand
{
    public function __construct(
        public WooDecision $dossier,
    ) {
    }
}
