<?php

declare(strict_types=1);

namespace App\Service\DocumentWorkflow;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;

class DocumentWorkflowStatus
{
    public function __construct(
        private readonly Document $document,
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

    public function canReplace(): bool
    {
        return $this->document->shouldBeUploaded(true);
    }
}
