<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use ApiPlatform\Metadata\ApiProperty;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Validator\ExactlyOneOf\ExactlyOneOf;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ExactlyOneOf(
    properties: ['mainDocument', 'noticeNotPublic'],
    noneMessage: 'dossier.document_or_notice_required',
    multipleMessage: 'dossier.document_and_notice_not_allowed',
)]
class AnnualReportRequestDto implements DossierRequestDtoInterface
{
    /**
     * @param list<AttachmentRequestDto> $attachments
     */
    public function __construct(
        #[ApiProperty(description: 'The `id` of the Department responsible for this publication.')]
        public Uuid $departmentId,
        #[ApiProperty(description: 'The `id` of the Subject this publication is categorised under, if applicable.')]
        public ?Uuid $subjectId,
        #[ApiProperty(description: 'A summary of the annual report.')]
        public string $summary,
        #[ApiProperty(description: 'The title of the dossier (3 to 500 characters).')]
        public DossierTitle $title,
        #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
        #[Assert\All([
            new Assert\Type(AttachmentRequestDto::class),
        ])]
        #[ApiProperty(description: 'The attachments that belong to this annual report (maximum '
            . AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER . ').')]
        public array $attachments,
        #[Assert\Length(4)]
        #[ApiProperty(description: 'The year this annual report covers (4 digits).')]
        public int $year,
        #[ApiProperty(description: 'The dossier number this publication is registered under; must be unique within the organisation.')]
        public string $dossierNumber,
        #[ApiProperty(description: 'The (planned) publication date of the annual report (format YYYY-MM-DD).')]
        public PlainDate $publicationDate,
        #[Assert\Valid]
        #[ApiProperty(description: 'The main document of the annual report.')]
        public ?AnnualReportMainDocumentRequestDto $mainDocument = null,
        #[Assert\Valid]
        #[ApiProperty(description: 'A notice explaining why (part of) this dossier will not be made public, provided '
            . 'instead of `mainDocument`. Shown on the public page in place of the document, citing the applicable '
            . 'exemption grounds.')]
        public ?NoticeNotPublicRequestDto $noticeNotPublic = null,
    ) {
    }
}
