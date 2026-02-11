<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\RequestForAdvice;

use DateTimeImmutable;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentRequestDto;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class RequestForAdviceRequestDto extends AbstractDossierRequestDto
{
    /**
     * @param list<AttachmentRequestDto> $attachments
     * @param list<string> $advisoryBodies
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
        public DateTimeImmutable $dossierDate,
        public string $dossierNumber,
        public DateTimeImmutable $publicationDate,
        #[Assert\Url]
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
