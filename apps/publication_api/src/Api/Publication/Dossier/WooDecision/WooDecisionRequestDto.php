<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision;

use ApiPlatform\Metadata\ApiProperty;
use DateTimeImmutable;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\Publication\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentRequestDto;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
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
        #[Assert\Valid]
        public MainDocumentRequestDto $mainDocument,
        public ?Uuid $subjectId,
        public string $summary,
        public string $title,
        #[Assert\All([
            new Assert\Type(AttachmentRequestDto::class),
        ])]
        #[Assert\Valid]
        public array $attachments,
        public PlainDate $dateFrom,
        public ?PlainDate $dateTo,
        public string $dossierNumber,
        public PlainDate $publicationDate,
        public DecisionType $decision,
        public PublicationReason $reason,
        #[ApiProperty(openapiContext: [
            'type' => 'string',
            'format' => 'date',
            'example' => '2025-12-21',
        ])]
        #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        public DateTimeImmutable $previewDate,
        #[Assert\All([
            new Assert\Type(WooDecisionDocumentRequestDto::class),
        ])]
        #[Assert\Valid]
        public array $documents = [],
    ) {
        parent::__construct($departmentId, $dossierNumber, $subjectId, $summary, $title);
    }
}
