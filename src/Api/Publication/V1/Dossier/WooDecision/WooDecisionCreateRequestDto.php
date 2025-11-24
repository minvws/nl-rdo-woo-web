<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\WooDecision;

use Shared\Api\Publication\V1\Attachment\AttachmentRequestDto;
use Shared\Api\Publication\V1\Dossier\AbstractDossierRequestDto;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class WooDecisionCreateRequestDto extends AbstractDossierRequestDto
{
    /**
     * @param list<AttachmentRequestDto> $attachments
     */
    public function __construct(
        public Uuid $departmentId,
        public string $internalReference,
        public WooDecisionMainDocumentRequestDto $mainDocument,
        public string $prefix,
        public ?Uuid $subjectId,
        public string $summary,
        public string $title,
        #[Assert\All([
            new Assert\Type(AttachmentRequestDto::class),
        ])]
        public array $attachments,
        public \DateTimeImmutable $dossierDateFrom,
        public ?\DateTimeImmutable $dossierDateTo,
        public string $dossierNumber,
        public \DateTimeImmutable $publicationDate,
        public DecisionType $decision,
        public PublicationReason $reason,
        public \DateTimeImmutable $previewDate,
    ) {
        parent::__construct($departmentId, $dossierNumber, $internalReference, $prefix, $subjectId, $summary, $title);
    }
}
