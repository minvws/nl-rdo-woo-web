<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Inquiry;
use App\Repository\DocumentRepository;

readonly class DocumentComparator
{
    public function __construct(
        private DocumentRepository $documentRepository,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function needsUpdate(Dossier $dossier, Document $document, DocumentMetadata $metadata): bool
    {
        // No comparison for 'id' and 'matter', these are part of the documentNr that was used to fetch $document, so they certainly match.

        if ($document->getJudgement() !== $metadata->getJudgement()) {
            return true;
        }

        // Important: compare by value, not by identity (!==)
        if ($document->getDocumentDate() != $metadata->getDate()) {
            return true;
        }

        if ($document->getFamilyId() !== $metadata->getFamilyId()) {
            return true;
        }

        if ($document->getThreadId() !== $metadata->getThreadId()) {
            return true;
        }

        if ($document->getGrounds() !== $metadata->getGrounds()) {
            return true;
        }

        if ($document->getSubjects() !== $metadata->getSubjects()) {
            return true;
        }

        if ($document->getPeriod() !== $metadata->getPeriod()) {
            return true;
        }

        if ($document->isSuspended() !== $metadata->isSuspended()) {
            return true;
        }

        if ($document->getLinks() !== $metadata->getLinks()) {
            return true;
        }

        if ($document->getRemark() !== $metadata->getRemark()) {
            return true;
        }

        $file = $document->getFileInfo();
        if ($file->getSourceType() !== $metadata->getSourceType()) {
            return true;
        }

        if ($file->getName() !== $metadata->getFilename($document->getDocumentNr())) {
            return true;
        }

        if ($this->hasCaseNrUpdate($document, $metadata)) {
            return true;
        }

        return $this->hasRefersToUpdate($dossier, $document, $metadata);
    }

    private function hasCaseNrUpdate(Document $document, DocumentMetadata $metadata): bool
    {
        $currentCaseNrs = $document->getInquiries()->map(
            fn (Inquiry $inquiry) => $inquiry->getCasenr()
        )->toArray();

        $newCaseNrs = $metadata->getCaseNumbers();

        return count($currentCaseNrs) !== count($newCaseNrs) || array_diff($currentCaseNrs, $newCaseNrs);
    }

    public function hasRefersToUpdate(Dossier $dossier, Document $document, DocumentMetadata $metadata): bool
    {
        $currentDocNrs = $document->getRefersTo()->map(
            fn (Document $doc) => $doc->getDocumentNr()
        )->toArray();

        $newDocNrs = [];
        foreach ($metadata->getRefersTo() as $referral) {
            $documentNr = DocumentNumber::fromReferral($dossier, $document, $referral);
            $referredDocument = $this->documentRepository->findByDocumentNumber($documentNr);
            if (! $referredDocument) {
                continue;
            }

            $newDocNrs[] = $referredDocument->getDocumentNr();
        }

        return count($currentDocNrs) !== count($newDocNrs) || array_diff($currentDocNrs, $newDocNrs);
    }
}
