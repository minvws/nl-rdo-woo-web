<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Command;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WithdrawReason;

readonly class WithDrawAllDocumentsCommand
{
    public function __construct(
        public WooDecision $dossier,
        public WithdrawReason $reason,
        public string $explanation,
    ) {
    }
}
