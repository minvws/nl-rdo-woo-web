<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Document;

use Doctrine\Common\Collections\Collection;
use PublicationApi\Domain\Upload\DocumentUploadStatusService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;

use function array_map;
use function array_values;

readonly class WooDecisionDocumentResponseDtoFactory
{
    public function __construct(
        private DocumentUploadStatusService $documentUploadStatusService,
        private WooDecisionRelatedDocumentResponseDtoFactory $wooDecisionRelatedDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,Document> $entities
     *
     * @return list<WooDecisionDocumentResponseDto>
     */
    public function fromEntities(array $entities): array
    {
        return array_values(array_map(self::fromEntity(...), $entities));
    }

    public function fromEntity(Document $document): WooDecisionDocumentResponseDto
    {
        return new WooDecisionDocumentResponseDto(
            $this->getCaseNumbers($document->getInquiries()),
            $document->getDocumentDate(),
            $document->getDocumentId(),
            $document->getDocumentNr(),
            $document->getExternalId(),
            $document->getFamilyId(),
            $document->getGrounds(),
            $document->isSuspended(),
            $document->isUploaded(),
            $document->isWithdrawn(),
            $document->getJudgement(),
            $document->getLinks(),
            $document->getPeriod(),
            $this->wooDecisionRelatedDocumentResponseDtoFactory->fromEntities($document->getRefersTo()->toArray()),
            $document->getRemark(),
            $document->getThreadId(),
            $this->documentUploadStatusService->getUploadStatus($document),
        );
    }

    /**
     * @param Collection<array-key,Inquiry> $inquiries
     *
     * @return list<string>
     */
    private function getCaseNumbers(Collection $inquiries): array
    {
        return array_values(array_map(fn (Inquiry $inquiry) => $inquiry->getCasenr(), $inquiries->toArray()));
    }
}
