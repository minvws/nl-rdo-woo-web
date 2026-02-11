<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision;

use DateTimeImmutable;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\Publication\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class WooDecisionRequestDto extends AbstractDossierRequestDto
{
    /**
     * @param list<AttachmentRequestDto> $attachments
     * @param list<WooDecisionDocumentRequestDto> $documents
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
        public DateTimeImmutable $dossierDateFrom,
        public ?DateTimeImmutable $dossierDateTo,
        public string $dossierNumber,
        public DateTimeImmutable $publicationDate,
        public DecisionType $decision,
        public PublicationReason $reason,
        public DateTimeImmutable $previewDate,
        #[Assert\All([
            new Assert\Type(WooDecisionDocumentRequestDto::class),
        ])]
        public array $documents = [],
    ) {
        parent::__construct($departmentId, $dossierNumber, $internalReference, $prefix, $subjectId, $summary, $title);
    }
}
