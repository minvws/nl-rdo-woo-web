<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Inquiry;

use Override;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Service\Inquiry\InquiryChangeset;
use Shared\Service\Inquiry\InquiryLinkUpdateResult;
use Shared\Service\Inquiry\InquiryService as SharedInquiryService;
use Symfony\Component\Uid\Uuid;

/**
 * Note: This is extending from the shared InquiryService in the Shared module. Ideally we should get an better
 * understanding of what we want to do in regards to removing Documents from inquiries for the UI. See issue #2868.
 */
final readonly class InquiryService extends SharedInquiryService
{
    #[Override]
    protected function handleDocumentDelete(
        Uuid $documentId,
        InquiryLinkUpdateResult $result,
    ): void {
        $document = $this->doctrine->getRepository(Document::class)->find($documentId);
        if ($document === null) {
            return;
        }

        $result->getInquiry()->removeDocument($document);
        $result->documentRemoved($document);
    }

    public function applyChangesetSync(InquiryChangeset $changeset): void
    {
        foreach ($changeset->getChanges() as $caseNr => $actions) {
            $caseNr = (string) $caseNr; // PHP will auto-cast numeric string keys to an int, we need it as a string

            $this->updateInquiryLinks(
                $changeset->getOrganisation(),
                $caseNr,
                $actions[InquiryChangeset::ADD_DOCUMENTS],
                $actions[InquiryChangeset::DEL_DOCUMENTS],
                $actions[InquiryChangeset::ADD_DOSSIERS],
            );
        }
    }
}
