<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Covenant;

use DateTimeImmutable;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentRequestDto;
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
        public string $internalReference,
        public MainDocumentRequestDto $mainDocument,
        public string $prefix,
        public ?Uuid $subjectId,
        public string $summary,
        public string $title,
        #[Assert\All([
            new Assert\Type(AttachmentRequestDto::class),
        ])]
        public array $attachments,
        public DateTimeImmutable $dateFrom,
        public ?DateTimeImmutable $dateTo,
        public string $dossierNumber,
        public DateTimeImmutable $publicationDate,
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
        #[Assert\Url]
        public string $previousVersionLink = '',
    ) {
        parent::__construct(
            $departmentId,
            $dossierNumber,
            $internalReference,
            $prefix,
            $subjectId,
            $summary,
            $title,
        );
    }
}
