<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Dossier\AbstractDossierRequestDto;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class DraftDecisionRequestDto extends AbstractDossierRequestDto
{
    /**
     * @param list<DraftDecisionAttachmentRequestDto> $attachments
     */
    public function __construct(
        public Uuid $departmentId,
        public ?Uuid $subjectId,
        public string $summary,
        public DossierTitle $title,
        #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
        #[Assert\All([
            new Assert\Type(DraftDecisionAttachmentRequestDto::class),
        ])]
        public array $attachments,
        public PlainDate $dossierDate,
        public string $dossierNumber,
        public PlainDate $publicationDate,
        #[Assert\NotNull]
        #[Assert\Valid]
        public ?DraftDecisionMainDocumentRequestDto $mainDocument = null,
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
