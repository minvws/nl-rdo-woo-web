<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentRepository;
use App\Service\Storage\EntityStorageService;

/**
 * This class will process updates to documents based on DocumentMetadata parsed from an inventory.
 */
readonly class DocumentUpdater
{
    public function __construct(
        private EntityStorageService $entityStorageService,
        private DocumentRepository $documentRepository,
        private DocumentDispatcher $documentDispatcher,
        private IngestDispatcher $ingestDispatcher,
    ) {
    }

    /**
     * Process DocumentMetadata, maps it to the document.
     *
     * NOTE: this method does not flush the changes to the database.
     *
     * @throws \Exception
     */
    public function databaseUpdate(DocumentMetadata $documentMetadata, WooDecision $dossier, Document $document): Document
    {
        $this->mapMetadataToDocument($documentMetadata, $document, $document->getDocumentNr());

        $document->addDossier($dossier);

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
        $this->ingestDispatcher->dispatchIngestMetadataOnlyCommand(
            $document->getId(),
            Document::class,
            ! $document->shouldBeUploaded(),
        );
    }

    public function databaseRemove(Document $document, WooDecision $dossier): void
    {
        $dossier->removeDocument($document);
    }

    public function asyncRemove(Document $document, WooDecision $dossier): void
    {
        $this->documentDispatcher->dispatchRemoveDocumentCommand($dossier->getId(), $document->getId());
    }

    private function mapMetadataToDocument(DocumentMetadata $documentMetadata, Document $document, string $documentNr): void
    {
        $document->setJudgement($documentMetadata->getJudgement());
        $document->setDocumentDate($documentMetadata->getDate());
        $document->setFamilyId($documentMetadata->getFamilyId());
        $document->setDocumentId($documentMetadata->getId());
        $document->setThreadId($documentMetadata->getThreadId());
        $document->setGrounds($documentMetadata->getGrounds());
        $document->setPeriod($documentMetadata->getPeriod());
        $document->setSuspended($documentMetadata->isSuspended());
        $document->setLinks($documentMetadata->getLinks());
        $document->setRemark($documentMetadata->getRemark());

        $fileName = $documentMetadata->getFilename($documentNr);

        $file = $document->getFileInfo();
        $file->setSourceType($documentMetadata->getSourceType());
        $file->setName($this->maxLen($fileName, 1024) ?? '');
    }

    private function removeObsoleteUpload(Document $document): void
    {
        if (! $document->shouldBeUploaded()) {
            $this->entityStorageService->deleteAllFilesForEntity($document);
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

    /**
     * @param string[] $refersTo
     */
    public function updateDocumentReferrals(WooDecision $dossier, Document $document, array $refersTo): void
    {
        // First convert '[matter]-[documentId]' string format to DocumentNumber instances that include the dossier prefix.
        $newReferrals = array_map(
            static fn (string $referral): DocumentNumber => DocumentNumber::fromReferral($dossier, $document, $referral),
            $refersTo,
        );

        $currentReferrals = $document->getRefersTo()->map(
            fn (Document $doc): DocumentNumber => DocumentNumber::fromDossierAndDocument($dossier, $doc),
        )->toArray();

        foreach (array_diff($currentReferrals, $newReferrals) as $referralToRemove) {
            $documentToRemove = $this->documentRepository->findByDocumentNumber($referralToRemove);
            if ($documentToRemove) {
                $document->removeReferralTo($documentToRemove);
            }
        }

        foreach (array_diff($newReferrals, $currentReferrals) as $referralToAdd) {
            $documentToAdd = $this->documentRepository->findByDocumentNumber($referralToAdd);
            if ($documentToAdd) {
                $document->addReferralTo($documentToAdd);
            }
        }
    }
}
