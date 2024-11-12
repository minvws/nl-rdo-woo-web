<?php

declare(strict_types=1);

namespace App\Service\DocumentWorkflow;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Document;
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

    public function replace(WooDecision $dossier, Document $document, UploadedFile $file): void
    {
        $status = $this->getStatus($document);
        if (! $status->canReplace()) {
            throw new \RuntimeException('Replace document action not allowed');
        }

        $this->documentService->replace($dossier, $document, $file);

        if ($document->isWithdrawn()) {
            $this->documentService->republish($document);
        }
    }
}
