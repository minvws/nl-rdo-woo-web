<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

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
class RequestForAdviceRequestDto implements DossierRequestDtoInterface
{
    /**
     * @param list<AttachmentRequestDto> $attachments
     * @param list<string> $advisoryBodies
     */
    public function __construct(
        #[ApiProperty(description: 'The `id` of the Department responsible for this publication.')]
        public Uuid $departmentId,
        #[ApiProperty(description: 'The `id` of the Subject this publication is categorised under, if applicable.')]
        public ?Uuid $subjectId,
        #[ApiProperty(description: 'A summary of the request for advice.')]
        public string $summary,
        #[ApiProperty(description: 'The title of the dossier (3 to 500 characters).')]
        public DossierTitle $title,
        #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
        #[Assert\All([
            new Assert\Type(AttachmentRequestDto::class),
        ])]
        #[ApiProperty(description: 'The attachments that belong to this request for advice (maximum '
            . AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER . ').')]
        public array $attachments,
        #[ApiProperty(description: 'The date of the request for advice (format YYYY-MM-DD).')]
        public PlainDate $dossierDate,
        #[ApiProperty(description: 'The dossier number this publication is registered under; must be unique within the organisation.')]
        public string $dossierNumber,
        #[ApiProperty(description: 'The (planned) publication date of the request for advice (format YYYY-MM-DD).')]
        public PlainDate $publicationDate,
        #[Assert\Url(requireTld: true)]
        #[ApiProperty(description: 'A link to the advice that resulted from this request for advice. Must be a valid '
            . 'URL, e.g. `https://www.example.org/advice`.')]
        public string $link,
        #[Assert\Count(
            max: 1,
        )]
        #[Assert\All(
            constraints: [
                new Assert\NotBlank(),
                new Assert\Length(min: 2, max: 100),
            ],
        )]
        #[ApiProperty(description: 'The advisory body that issued or will issue the advice (legal entity name, 2 to 100 characters; at most one).')]
        public array $advisoryBodies,
        #[Assert\Valid]
        #[ApiProperty(description: 'The main document of the request for advice.')]
        public ?RequestForAdviceMainDocumentRequestDto $mainDocument = null,
        #[Assert\Valid]
        #[ApiProperty(description: 'A notice explaining why (part of) this dossier will not be made public, provided '
            . 'instead of `mainDocument`. Shown on the public page in place of the document, citing the applicable '
            . 'exemption grounds.')]
        public ?NoticeNotPublicRequestDto $noticeNotPublic = null,
    ) {
    }
}
