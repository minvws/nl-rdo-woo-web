<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Event;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\WithdrawReason;

readonly class DocumentWithDrawnEvent
{
    public function __construct(
        public Document $document,
        public WithdrawReason $reason,
        public string $explanation,
    ) {
    }
}
