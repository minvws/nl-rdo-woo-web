<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use ApiPlatform\Metadata\ApiProperty;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Service\Inventory\InventoryRunProcessor;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class WooDecisionRequestDto implements DossierRequestDtoInterface
{
    /**
     * @param list<AttachmentRequestDto> $attachments
     * @param list<WooDecisionDocumentRequestDto> $documents
     */
    public function __construct(
        #[ApiProperty(description: 'The `id` of the Department responsible for this publication.')]
        public Uuid $departmentId,
        #[Assert\Valid]
        #[ApiProperty(description: 'The main document of the decision, for example the decision letter.')]
        public WooDecisionMainDocumentRequestDto $mainDocument,
        #[ApiProperty(description: 'The `id` of the Subject this publication is categorised under, if applicable.')]
        public ?Uuid $subjectId,
        #[ApiProperty(description: 'A summary of the decision.')]
        public string $summary,
        #[ApiProperty(description: 'The title of the dossier (3 to 500 characters).')]
        public DossierTitle $title,
        #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
        #[Assert\All([
            new Assert\Type(AttachmentRequestDto::class),
        ])]
        #[Assert\Valid]
        #[ApiProperty(description: 'The attachments that belong to this decision (maximum '
            . AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER . ').')]
        public array $attachments,
        #[ApiProperty(description: 'Start date of the period the documents in this decision relate to (format YYYY-MM-DD).')]
        public PlainDate $dateFrom,
        #[ApiProperty(description: 'End date of the period the documents in this decision relate to (format YYYY-MM-DD).')]
        public ?PlainDate $dateTo,
        #[ApiProperty(description: 'The dossier number this publication is registered under; must be unique within the organisation.')]
        public string $dossierNumber,
        #[ApiProperty(description: 'The (planned) publication date of the decision (format YYYY-MM-DD).')]
        public PlainDate $publicationDate,
        #[ApiProperty(description: 'The type of decision, for example public, partially public, not public or already public.')]
        public DecisionType $decision,
        #[ApiProperty(description: 'The legal basis of the request: a Wob or a Woo request.')]
        public PublicationReason $reason,
        #[ApiProperty(description: 'The date on which the publication is made available as a preview, ahead of the publication date.')]
        public PlainDate $previewDate,
        #[Assert\Count(max: InventoryRunProcessor::MAX_DOCUMENTS)]
        #[Assert\All([
            new Assert\Type(WooDecisionDocumentRequestDto::class),
        ])]
        #[Assert\Valid]
        #[Assert\Unique(message: 'woo_decision.duplicate_document_external_id', normalizer: [self::class, 'normalizeDocumentExternalId'])]
        #[Assert\Unique(message: 'woo_decision.duplicate_document_id', normalizer: [self::class, 'normalizeDocumentDocumentId'])]
        #[ApiProperty(description: 'The documents that are part of this decision (maximum '
            . InventoryRunProcessor::MAX_DOCUMENTS . '). The externalId and documentId of each document '
            . 'must be unique within the dossier.')]
        public array $documents = [],
    ) {
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
