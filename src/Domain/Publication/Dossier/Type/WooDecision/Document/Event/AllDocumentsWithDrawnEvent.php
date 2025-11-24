<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class AllDocumentsWithDrawnEvent
{
    public function __construct(
        public WooDecision $dossier,
        public DocumentWithdrawReason $reason,
        public string $explanation,
    ) {
    }
}
