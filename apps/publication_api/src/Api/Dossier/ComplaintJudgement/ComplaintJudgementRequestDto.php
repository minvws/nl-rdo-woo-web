<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use ApiPlatform\Metadata\ApiProperty;
use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
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
class ComplaintJudgementRequestDto implements DossierRequestDtoInterface
{
    public function __construct(
        #[ApiProperty(description: 'The `id` of the Department responsible for this publication.')]
        public Uuid $departmentId,
        #[ApiProperty(description: 'The `id` of the Subject this publication is categorised under, if applicable.')]
        public ?Uuid $subjectId,
        #[ApiProperty(description: 'A summary of the complaint judgement.')]
        public string $summary,
        #[ApiProperty(description: 'The title of the dossier (3 to 500 characters).')]
        public DossierTitle $title,
        #[ApiProperty(description: 'The date of the complaint judgement (format YYYY-MM-DD).')]
        public PlainDate $dossierDate,
        #[ApiProperty(description: 'The dossier number this publication is registered under; must be unique within the organisation.')]
        public string $dossierNumber,
        #[ApiProperty(description: 'The (planned) publication date of the complaint judgement (format YYYY-MM-DD).')]
        public PlainDate $publicationDate,
        #[Assert\Valid]
        #[ApiProperty(description: 'The main document of the complaint judgement.')]
        public ?ComplaintJudgementMainDocumentRequestDto $mainDocument = null,
        #[Assert\Valid]
        #[ApiProperty(description: 'A notice explaining why (part of) this dossier will not be made public, provided '
            . 'instead of `mainDocument`. Shown on the public page in place of the document, citing the applicable '
            . 'exemption grounds.')]
        public ?NoticeNotPublicRequestDto $noticeNotPublic = null,
    ) {
    }
}
