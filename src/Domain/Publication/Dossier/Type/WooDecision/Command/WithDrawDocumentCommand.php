<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Command;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Document;
use App\Entity\WithdrawReason;

readonly class WithDrawDocumentCommand
{
    public function __construct(
        public WooDecision $dossier,
        public Document $document,
        public WithdrawReason $reason,
        public string $explanation,
    ) {
    }
}
