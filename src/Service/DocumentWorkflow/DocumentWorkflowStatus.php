<?php

declare(strict_types=1);

namespace App\Service\DocumentWorkflow;

use App\Entity\Document;

class DocumentWorkflowStatus
{
    public function __construct(
        private readonly Document $document,
    ) {
    }

    public function canWithdraw(): bool
    {
        return $this->document->shouldBeUploaded() && ! $this->document->isWithdrawn();
    }

    public function canReplace(): bool
    {
        return $this->document->shouldBeUploaded(true);
    }
}
