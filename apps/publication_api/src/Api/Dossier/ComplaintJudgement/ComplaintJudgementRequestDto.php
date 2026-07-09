<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
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
class ComplaintJudgementRequestDto extends AbstractDossierRequestDto
{
    public function __construct(
        public Uuid $departmentId,
        public ?Uuid $subjectId,
        public string $summary,
        public DossierTitle $title,
        public PlainDate $dossierDate,
        public string $dossierNumber,
        public PlainDate $publicationDate,
        #[Assert\Valid]
        public ?ComplaintJudgementMainDocumentRequestDto $mainDocument = null,
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
