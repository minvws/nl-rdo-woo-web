<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\OtherPublication;

use Shared\Api\Publication\V1\Attachment\AttachmentRequestDto;
use Shared\Api\Publication\V1\Dossier\AbstractDossierRequestDto;
use Shared\Api\Publication\V1\MainDocument\MainDocumentRequestDto;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class OtherPublicationCreateRequestDto extends AbstractDossierRequestDto
{
    /**
     * @param list<AttachmentRequestDto> $attachments
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
        public \DateTimeImmutable $dossierDate,
        public string $dossierNumber,
        public \DateTimeImmutable $publicationDate,
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
