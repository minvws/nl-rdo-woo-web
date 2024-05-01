<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Event;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\WithdrawReason;

readonly class AllDocumentsWithDrawnEvent
{
    public function __construct(
        public WooDecision $dossier,
        public WithdrawReason $reason,
        public string $explanation,
    ) {
    }
}
