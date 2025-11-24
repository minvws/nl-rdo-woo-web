<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;

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
