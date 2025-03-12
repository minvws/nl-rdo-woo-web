<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document\Event;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class AllDocumentsWithDrawnEvent
{
    public function __construct(
        public WooDecision $dossier,
        public DocumentWithdrawReason $reason,
        public string $explanation,
    ) {
    }
}
