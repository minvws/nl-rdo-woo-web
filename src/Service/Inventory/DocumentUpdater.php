<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use Exception;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\ValueObject\ExternalId;

use function array_diff;
use function array_map;
use function is_null;
use function strlen;
use function substr;

/**
 * This class will process updates to documents based on DocumentMetadata parsed from an inventory.
 */
readonly class DocumentUpdater
{
    public function __construct(
        private EntityStorageService $entityStorageService,
        private ThumbnailStorageService $thumbStorage,
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
     * @throws Exception
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
            $this->thumbStorage->deleteAllThumbsForEntity($document);
            $document->getFileInfo()->removeFileProperties();
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
    public function updateDocumentReferralsByDocumentNumber(WooDecision $dossier, Document $document, array $refersTo): void
    {
        // First convert '[matter]-[documentId]' string format to DocumentNumber instances that include the dossier prefix.
        $newReferrals = array_map(
            static fn (string $referral): DocumentNumber => DocumentNumber::fromReferral($dossier, $document, $referral),
            $refersTo,
        );

        $currentReferrals = $document->getRefersTo()->map(
            fn (Document $doc): DocumentNumber => DocumentNumber::fromDossierAndDocument($dossier, $doc),
        )->toArray();

        foreach (array_diff($currentReferrals, $newReferrals) as $refersToRemove) {
            $documentToRemove = $this->documentRepository->findByDocumentNumber($refersToRemove);
            if ($documentToRemove) {
                $document->removeRefersTo($documentToRemove);
            }
        }

        foreach (array_diff($newReferrals, $currentReferrals) as $refersToAdd) {
            $documentToAdd = $this->documentRepository->findByDocumentNumber($refersToAdd);

            if ($documentToAdd) {
                $document->addRefersTo($documentToAdd);
            }
        }
    }

    /**
     * @param ExternalId[] $refersTo
     */
    public function updateDocumentReferralsByDocumentExternalId(Document $document, array $refersTo): void
    {
        /** @var ExternalId[] $currentReferrals */
        $currentReferrals = $document->getRefersTo()
            ->map(static function (Document $document): ?ExternalId {
                if ($document->getExternalId() === null) {
                    return null;
                }

                return $document->getExternalId();
            })
            ->filter(static fn (?ExternalId $value): bool => $value !== null)
            ->toArray();

        foreach (array_diff($currentReferrals, $refersTo) as $refersToRemove) {
            $documentToRemove = $this->documentRepository->findByExternalId($refersToRemove);
            if ($documentToRemove) {
                $document->removeRefersTo($documentToRemove);
            }
        }

        foreach (array_diff($refersTo, $currentReferrals) as $refersToAdd) {
            $documentToAdd = $this->documentRepository->findByExternalId($refersToAdd);

            if ($documentToAdd) {
                $document->addRefersTo($documentToAdd);
            }
        }
    }
}
