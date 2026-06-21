<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Service\Inventory\InventoryRunProcessor;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class WooDecisionRequestDto extends AbstractDossierRequestDto
{
    /**
     * @param list<AttachmentRequestDto> $attachments
     * @param list<WooDecisionDocumentRequestDto> $documents
     */
    public function __construct(
        public Uuid $departmentId,
        #[Assert\Valid]
        public WooDecisionMainDocumentRequestDto $mainDocument,
        public ?Uuid $subjectId,
        public string $summary,
        public DossierTitle $title,
        #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
        #[Assert\All([
            new Assert\Type(AttachmentRequestDto::class),
        ])]
        #[Assert\Valid]
        public array $attachments,
        public PlainDate $dateFrom,
        public ?PlainDate $dateTo,
        public string $dossierNumber,
        public PlainDate $publicationDate,
        public DecisionType $decision,
        public PublicationReason $reason,
        public PlainDate $previewDate,
        #[Assert\Count(max: InventoryRunProcessor::MAX_DOCUMENTS)]
        #[Assert\All([
            new Assert\Type(WooDecisionDocumentRequestDto::class),
        ])]
        #[Assert\Valid]
        #[Assert\Unique(normalizer: [self::class, 'normalizeDocumentExternalId'], message: 'woo_decision.duplicate_document_external_id')]
        #[Assert\Unique(normalizer: [self::class, 'normalizeDocumentDocumentId'], message: 'woo_decision.duplicate_document_id')]
        public array $documents = [],
    ) {
        parent::__construct($departmentId, $dossierNumber, $subjectId, $summary, $title);
    }

    public static function normalizeDocumentDocumentId(WooDecisionDocumentRequestDto $document): string
    {
        return $document->documentId->toString();
    }

    public static function normalizeDocumentExternalId(WooDecisionDocumentRequestDto $document): string
    {
        return $document->externalId->toString();
    }
}
