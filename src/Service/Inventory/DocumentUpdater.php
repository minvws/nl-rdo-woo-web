<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Message\IngestMetadataOnlyMessage;
use App\Message\RemoveDocumentMessage;
use App\Repository\DocumentRepository;
use App\Service\Inquiry\InquiryService;
use App\Service\Storage\DocumentStorageService;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * This class will process updates to documents based on DocumentMetadata parsed from an inventory.
 */
class DocumentUpdater
{
    public function __construct(
        private readonly InquiryService $inquiryService,
        private readonly MessageBusInterface $messageBus,
        private readonly DocumentStorageService $documentStorage,
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    /**
     * Process DocumentMetadata, maps it to the document.
     * Adds document to the dossier if needed and also generates or updates inquiries/cases.
     *
     * NOTE: this method does not flush the changes to the database.
     *
     * @throws \Exception
     */
    public function databaseUpdate(DocumentMetadata $documentMetadata, Dossier $dossier, Document $document): Document
    {
        $this->mapMetadataToDocument($documentMetadata, $document, $document->getDocumentNr());

        $dossier->addDocument($document);

        // Add document to woo request case nr (if any), or create new woo request if not already present
        $this->inquiryService->updateDocumentInquiries(
            $document,
            array_combine($documentMetadata->getCaseNumbers(), $documentMetadata->getCaseNumbers())
        );

        $this->removeObsoleteUpload($document);

        $this->documentRepository->save($document);

        return $document;
    }

    /*
     * Update the metadata for the document in ES.
     * - if we no longer expect an upload remove any existing pages by setting refresh to true
     * - otherwise only update document metadata and leave the pages as is
     */
    public function asyncUpdate(Document $document): void
    {
        $this->messageBus->dispatch(
            new IngestMetadataOnlyMessage($document->getId(), ! $document->shouldBeUploaded())
        );
    }

    public function databaseRemove(Document $document, Dossier $dossier): void
    {
        $dossier->removeDocument($document);
    }

    public function asyncRemove(Document $document, Dossier $dossier): void
    {
        $this->messageBus->dispatch(
            RemoveDocumentMessage::forDossierAndDocument($dossier, $document)
        );
    }

    private function mapMetadataToDocument(DocumentMetadata $documentMetadata, Document $document, string $documentNr): void
    {
        $document->setJudgement($documentMetadata->getJudgement());
        $document->setDocumentDate($documentMetadata->getDate());
        $document->setFamilyId($documentMetadata->getFamilyId());
        $document->setDocumentId($documentMetadata->getId());
        $document->setThreadId($documentMetadata->getThreadId());
        $document->setGrounds($documentMetadata->getGrounds());
        $document->setSubjects($documentMetadata->getSubjects());
        $document->setPeriod($documentMetadata->getPeriod());
        $document->setSuspended($documentMetadata->isSuspended());
        $document->setLink($this->maxLen($documentMetadata->getLink(), 2048));
        $document->setRemark($documentMetadata->getRemark());

        $fileName = $documentMetadata->getFilename($documentNr);

        $file = $document->getFileInfo();
        $file->setSourceType($documentMetadata->getSourceType());
        $file->setName($this->maxLen($fileName, 1024) ?? '');
    }

    private function removeObsoleteUpload(Document $document): void
    {
        if (! $document->shouldBeUploaded()) {
            $this->documentStorage->deleteAllFilesForDocument($document);
            $document->getFileInfo()->removeFileProperties();
            $document->setPageCount(0);
        }
    }

    protected function maxLen(?string $subject, int $maxSize): ?string
    {
        if (is_null($subject)) {
            return $subject;
        }

        if (strlen($subject) > $maxSize) {
            return substr($subject, 0, $maxSize);
        }

        return $subject;
    }
}
