<?php

declare(strict_types=1);

namespace App\Service\DocumentWorkflow;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;

readonly class DocumentWorkflowStatus
{
    public function __construct(
        private Document $document,
    ) {
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function canWithdraw(): bool
    {
        return $this->document->shouldBeUploaded() && ! $this->document->isWithdrawn();
    }
}
