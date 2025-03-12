<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document\Event;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;

readonly class DocumentWithDrawnEvent
{
    public function __construct(
        public Document $document,
        public DocumentWithdrawReason $reason,
        public string $explanation,
        public bool $bulkAction,
    ) {
    }

    public function isBulkAction(): bool
    {
        return $this->bulkAction;
    }
}
