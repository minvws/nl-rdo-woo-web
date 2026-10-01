<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use Exception;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ObsoleteFileRemover;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\ExternalId;

use function array_diff;
use function array_keys;
use function grapheme_strlen;
use function grapheme_substr;

/**
 * This class will process updates to documents based on DocumentMetadata parsed from an inventory.
 */
readonly class DocumentUpdater
{
    public function __construct(
        private ObsoleteFileRemover $obsoleteFileRemover,
        private DocumentRepository $documentRepository,
        private DocumentDispatcher $documentDispatcher,
        private IngestDispatcher $ingestDispatcher,
    ) {
    }

    public function linkDocument(Document $document, WooDecision $dossier): void
    {
        $document->addDossier($dossier);

        $this->documentRepository->save($document);
    }

    /**
     * @throws Exception
     */
    public function updateMetadata(DocumentMetadata $documentMetadata, Document $document): void
    {
        $this->mapMetadataToDocument($documentMetadata, $document, $document->getDocumentNumber());

        $this->obsoleteFileRemover->removeIfObsolete($document);
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

    private function mapMetadataToDocument(
        DocumentMetadata $documentMetadata,
        Document $document,
        DocumentNumber $documentNumber,
    ): void {
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
        $document->setPublicationContext($documentMetadata->getPublicationContext());

        $fileName = $documentMetadata->getFilename($documentNumber);

        $file = $document->getFileInfo();
        $file->setSourceType($documentMetadata->getSourceType());
        $file->setName($this->buildName($fileName));
    }

    private function buildName(?string $subject): string
    {
        if ($subject === null) {
            return '';
        }

        $maxLength = 1024;
        if (grapheme_strlen($subject) > $maxLength) {
            $subject = grapheme_substr($subject, 0, $maxLength);
            if ($subject === false) {
                $subject = '';
            }
        }

        return $subject;
    }

    /**
     * @param array<array-key, string> $refersTo
     */
    public function updateDocumentReferralsByDocumentNumber(Document $document, array $refersTo): void
    {
        /** @var array<string, DocumentNumber> $newReferrals */
        $newReferrals = [];
        foreach ($refersTo as $referral) {
            $documentNumber = DocumentNumber::fromString($referral);
            $newReferrals[$documentNumber->toString()] = $documentNumber;
        }

        /** @var array<string, DocumentNumber> $currentReferrals */
        $currentReferrals = [];
        foreach ($document->getRefersTo() as $referredDocument) {
            $documentNumber = $referredDocument->getDocumentNumber();
            $currentReferrals[$documentNumber->toString()] = $documentNumber;
        }

        foreach (array_diff(array_keys($currentReferrals), array_keys($newReferrals)) as $refersToRemove) {
            $documentToRemove = $this->documentRepository->findByDocumentNumber($currentReferrals[$refersToRemove]);
            if ($documentToRemove) {
                $document->removeRefersTo($documentToRemove);
            }
        }

        foreach (array_diff(array_keys($newReferrals), array_keys($currentReferrals)) as $refersToAdd) {
            $documentToAdd = $this->documentRepository->findByDocumentNumber($newReferrals[$refersToAdd]);

            if ($documentToAdd) {
                $document->addRefersTo($documentToAdd);
            }
        }
    }

    /**
     * @param array<array-key, ExternalId> $refersTo
     */
    public function updateDocumentReferralsByDocumentExternalId(Document $document, array $refersTo): void
    {
        /** @var array<array-key, ExternalId> $currentReferrals */
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
