<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Command;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WithdrawReason;

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
