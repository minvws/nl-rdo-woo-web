<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

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
class CovenantRequestDto extends AbstractDossierRequestDto
{
    /**
     * @param list<AttachmentRequestDto> $attachments
     * @param list<string> $parties
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
        public PlainDate $dateFrom,
        public ?PlainDate $dateTo,
        public string $dossierNumber,
        public PlainDate $publicationDate,
        #[Assert\Count(
            min: 2,
            max: 10,
        )]
        #[Assert\All(
            constraints: [
                new Assert\NotBlank(),
                new Assert\Length(min: 2, max: 100),
            ],
        )]
        public array $parties,
        #[Assert\Url(requireTld: true)]
        public string $previousVersionLink = '',
        #[Assert\Valid]
        public ?CovenantMainDocumentRequestDto $mainDocument = null,
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
