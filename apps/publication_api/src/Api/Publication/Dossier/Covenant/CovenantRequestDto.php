<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Covenant;

use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentRequestDto;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class CovenantRequestDto extends AbstractDossierRequestDto
{
    /**
     * @param list<AttachmentRequestDto> $attachments
     * @param list<string> $parties
     */
    public function __construct(
        public Uuid $departmentId,
        public MainDocumentRequestDto $mainDocument,
        public ?Uuid $subjectId,
        public string $summary,
        public string $title,
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
