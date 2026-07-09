<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\AbstractDossierRequestDto;
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
class RequestForAdviceRequestDto extends AbstractDossierRequestDto
{
    /**
     * @param list<AttachmentRequestDto> $attachments
     * @param list<string> $advisoryBodies
     */
    public function __construct(
        public Uuid $departmentId,
        public ?Uuid $subjectId,
        public string $summary,
        public DossierTitle $title,
        #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
        #[Assert\All([
            new Assert\Type(AttachmentRequestDto::class),
        ])]
        public array $attachments,
        public PlainDate $dossierDate,
        public string $dossierNumber,
        public PlainDate $publicationDate,
        #[Assert\Url(requireTld: true)]
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
        public array $advisoryBodies,
        #[Assert\Valid]
        public ?RequestForAdviceMainDocumentRequestDto $mainDocument = null,
        #[Assert\Valid]
        public ?NoticeNotPublicRequestDto $noticeNotPublic = null,
    ) {
        parent::__construct(
            $departmentId,
            $dossierNumber,
            $subjectId,
            $summary,
            $title,
        );
    }
}
