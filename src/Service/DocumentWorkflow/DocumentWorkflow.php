<?php

declare(strict_types=1);

namespace App\Service\DocumentWorkflow;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\WithdrawReason;
use App\Service\DocumentService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This class implements all actions that can be applied to a document.
 */
class DocumentWorkflow
{
    public function __construct(
        private readonly DocumentService $documentService,
    ) {
    }

    public function getStatus(Document $document): DocumentWorkflowStatus
    {
        return new DocumentWorkflowStatus($document);
    }

    public function withdraw(Document $document, WithdrawReason $reason, string $explanation): void
    {
        $status = $this->getStatus($document);
        if (! $status->canWithdraw()) {
            throw new \RuntimeException('Withdraw document action not allowed');
        }

        $this->documentService->withdraw($document, $reason, $explanation);
    }

    public function replace(Dossier $dossier, Document $document, UploadedFile $file): void
    {
        $status = $this->getStatus($document);
        if (! $status->canReplace()) {
            throw new \RuntimeException('Replace document action not allowed');
        }

        $this->documentService->replace($dossier, $document, $file);
    }

    public function republish(Document $document): void
    {
        $status = $this->getStatus($document);
        if (! $status->canRepublish()) {
            throw new \RuntimeException('Republish document action not allowed');
        }

        $this->documentService->republish($document);
    }
}
