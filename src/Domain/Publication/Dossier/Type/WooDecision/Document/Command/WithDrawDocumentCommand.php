<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document\Command;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Symfony\Component\Uid\Uuid;

readonly class WithDrawDocumentCommand
{
    public function __construct(
        public Uuid $dossierId,
        public Uuid $documentId,
        public DocumentWithdrawReason $reason,
        public string $explanation,
    ) {
    }
}
