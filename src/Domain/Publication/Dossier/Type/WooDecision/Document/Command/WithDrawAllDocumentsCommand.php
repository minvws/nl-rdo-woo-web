<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document\Command;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Symfony\Component\Uid\Uuid;

readonly class WithDrawAllDocumentsCommand
{
    public function __construct(
        public Uuid $dossierId,
        public DocumentWithdrawReason $reason,
        public string $explanation,
    ) {
    }
}
