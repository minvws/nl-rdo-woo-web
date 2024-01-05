<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\Document;
use App\Entity\Inquiry;

class DocumentComparator
{
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function needsUpdate(Document $document, DocumentMetadata $metadata): bool
    {
        // No comparison for 'id' and 'matter', these are part of the documentNr that was used to fetch $document, so they certainly match.

        if ($document->getJudgement() !== $metadata->getJudgement()) {
            return true;
        }

        // Comparison is intentionally non-strict because we compare DateTime with DateTimeImmutable
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

        return $this->hasCaseNrUpdate($document, $metadata);
    }

    private function hasCaseNrUpdate(Document $document, DocumentMetadata $metadata): bool
    {
        $currentDocNrs = $document->getInquiries()->map(
            /* @phpstan-ignore-next-line */
            fn (Inquiry $inquiry) => $inquiry->getCasenr()
        )->toArray();

        $newDocNrs = $metadata->getCaseNumbers();

        return count($currentDocNrs) !== count($newDocNrs) || array_diff($currentDocNrs, $newDocNrs);
    }
}
