<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Disposition;

use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentRequestDto;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class DispositionRequestDto extends AbstractDossierRequestDto
{
    /**
     * @param list<AttachmentRequestDto> $attachments
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
        public PlainDate $dossierDate,
        public string $dossierNumber,
        public PlainDate $publicationDate,
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
