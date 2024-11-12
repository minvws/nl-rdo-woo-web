<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Document;
use App\Entity\Inquiry;
use App\Repository\DocumentRepository;

readonly class DocumentComparator
{
    public function __construct(
        private DocumentRepository $documentRepository,
    ) {
    }

    public function needsUpdate(WooDecision $dossier, Document $document, DocumentMetadata $metadata): bool
    {
        return $this->getChangeset($dossier, $document, $metadata)->hasChanges();
    }

    public function getChangeset(WooDecision $dossier, Document $document, DocumentMetadata $metadata): PropertyChangeset
    {
        $changeset = new PropertyChangeset();

        // No comparison for 'id' and 'matter', these are part of the documentNr that was used to fetch $document, so they certainly match.

        $changeset->compare(MetadataField::JUDGEMENT->value, $document->getJudgement(), $metadata->getJudgement());
        $changeset->compare(MetadataField::FAMILY->value, $document->getFamilyId(), $metadata->getFamilyId());
        $changeset->compare(MetadataField::THREADID->value, $document->getThreadId(), $metadata->getThreadId());
        $changeset->compare(MetadataField::GROUND->value, $document->getGrounds(), $metadata->getGrounds());
        $changeset->compare('period', $document->getPeriod(), $metadata->getPeriod());
        $changeset->compare(MetadataField::SUSPENDED->value, $document->isSuspended(), $metadata->isSuspended());
        $changeset->compare(MetadataField::LINK->value, $document->getLinks(), $metadata->getLinks());
        $changeset->compare(MetadataField::REMARK->value, $document->getRemark(), $metadata->getRemark());
        $changeset->compare(
            MetadataField::DATE->value,
            $document->getDocumentDate()?->format('Y-m-d'),
            $metadata->getDate()?->format('Y-m-d'),
        );
        $changeset->compare(
            MetadataField::SOURCETYPE->value,
            $document->getFileInfo()->getSourceType(),
            $metadata->getSourceType()
        );
        $changeset->compare(
            MetadataField::DOCUMENT->value,
            $document->getFileInfo()->getName(),
            $metadata->getFilename($document->getDocumentNr()),
        );

        if ($this->hasCaseNrUpdate($document, $metadata)) {
            $changeset->add(MetadataField::CASENR->value);
        }

        if ($this->hasRefersToUpdate($dossier, $document, $metadata)) {
            $changeset->add(MetadataField::REFERS_TO->value);
        }

        return $changeset;
    }

    private function hasCaseNrUpdate(Document $document, DocumentMetadata $metadata): bool
    {
        $currentCaseNrs = $document->getInquiries()->map(
            fn (Inquiry $inquiry) => $inquiry->getCasenr()
        )->toArray();

        $newCaseNrs = $metadata->getCaseNumbers();

        return count($currentCaseNrs) !== count($newCaseNrs) || array_diff($currentCaseNrs, $newCaseNrs);
    }

    public function hasRefersToUpdate(WooDecision $dossier, Document $document, DocumentMetadata $metadata): bool
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
