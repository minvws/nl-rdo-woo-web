<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

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
class AnnualReportRequestDto extends AbstractDossierRequestDto
{
    /**
     * @param list<AttachmentRequestDto> $attachments
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
        #[Assert\Length(4)]
        public int $year,
        public string $dossierNumber,
        public PlainDate $publicationDate,
        #[Assert\Valid]
        public ?AnnualReportMainDocumentRequestDto $mainDocument = null,
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
