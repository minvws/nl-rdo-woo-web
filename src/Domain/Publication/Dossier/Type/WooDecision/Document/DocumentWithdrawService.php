<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document;

use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Exception\DocumentWorkflowException;
use Shared\Service\DocumentWorkflow\DocumentWorkflowStatus;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;

readonly class DocumentWithdrawService
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private EntityStorageService $entityStorageService,
        private ThumbnailStorageService $thumbStorage,
        private IngestDispatcher $ingestDispatcher,
        private DocumentDispatcher $documentDispatcher,
    ) {
    }

    public function withdraw(
        Document $document,
        DocumentWithdrawReason $reason,
        string $explanation,
        bool $bulkAction = false,
    ): void {
        $status = new DocumentWorkflowStatus($document);
        if (! $status->canWithdraw()) {
            throw DocumentWorkflowException::forActionNotAllowed($document, 'withdraw');
        }

        $this->entityStorageService->deleteAllFilesForEntity($document);
        $this->thumbStorage->deleteAllThumbsForEntity($document);

        $document->withdraw($reason, $explanation);
        $this->documentRepository->save($document, true);

        // Re-ingest the document, this will update all file metadata and overwrite any existing page content with an empty set.
        $this->ingestDispatcher->dispatchIngestMetadataOnlyCommandForEntity($document, true);

        $this->documentDispatcher->dispatchDocumentWithdrawnEvent($document, $reason, $explanation, $bulkAction);
    }

    public function withDrawAllDocuments(WooDecision $wooDecision, DocumentWithdrawReason $reason, string $explanation): void
    {
        foreach ($wooDecision->getDocuments() as $document) {
            try {
                $this->withdraw($document, $reason, $explanation, true);
            } catch (DocumentWorkflowException) {
                // If the document status does not allow document withdraw that's ok, continue with the rest
            }
        }

        $this->documentDispatcher->dispatchAllDocumentsWithdrawnEvent($wooDecision, $reason, $explanation);
    }
}
