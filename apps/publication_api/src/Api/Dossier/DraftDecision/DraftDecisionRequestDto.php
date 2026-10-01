<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use ApiPlatform\Metadata\ApiProperty;
use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class DraftDecisionRequestDto implements DossierRequestDtoInterface
{
    /**
     * @param list<DraftDecisionAttachmentRequestDto> $attachments
     */
    public function __construct(
        #[ApiProperty(description: 'The `id` of the Department responsible for this publication.')]
        public Uuid $departmentId,
        #[ApiProperty(description: 'The `id` of the Subject this publication is categorised under, if applicable.')]
        public ?Uuid $subjectId,
        #[ApiProperty(description: 'A summary of the draft decision.')]
        public string $summary,
        #[ApiProperty(description: 'The title of the dossier (3 to 500 characters).')]
        public DossierTitle $title,
        #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
        #[Assert\All([
            new Assert\Type(DraftDecisionAttachmentRequestDto::class),
        ])]
        #[ApiProperty(description: 'The attachments that belong to this draft decision (maximum '
            . AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER . ').')]
        public array $attachments,
        #[ApiProperty(description: 'The date of the draft decision (format YYYY-MM-DD).')]
        public PlainDate $dossierDate,
        #[ApiProperty(description: 'The dossier number this publication is registered under; must be unique within the organisation.')]
        public string $dossierNumber,
        #[ApiProperty(description: 'The (planned) publication date of the draft decision (format YYYY-MM-DD).')]
        public PlainDate $publicationDate,
        #[Assert\NotNull]
        #[Assert\Valid]
        #[ApiProperty(description: 'The main document of the draft decision.')]
        public ?DraftDecisionMainDocumentRequestDto $mainDocument = null,
    ) {
    }
}
