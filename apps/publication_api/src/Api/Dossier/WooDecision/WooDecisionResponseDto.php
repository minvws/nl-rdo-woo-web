<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use PublicationApi\Api\Attachment\AttachmentResponseDto;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentResponseDto;
use PublicationApi\Api\MainDocument\MainDocumentResponseDto;
use PublicationApi\Api\Organisation\OrganisationResponseDto;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

final class WooDecisionResponseDto
{
    /**
     * @param list<AttachmentResponseDto> $attachments
     * @param list<WooDecisionDocumentResponseDto> $documents
     */
    final public function __construct(
        public Uuid $id,
        public ?ExternalId $externalId,
        public OrganisationResponseDto $organisation,
        public string $dossierNumber,
        public ?string $title,
        public string $summary,
        public ?string $subject,
        public DepartmentResponseDto $department,
        public ?PlainDate $publicationDate,
        public DossierStatus $status,
        public MainDocumentResponseDto $mainDocument,
        public array $attachments,
        public ?PlainDate $dateFrom,
        public ?PlainDate $dateTo,
        public ?DecisionType $decision,
        public PublicationReason $reason,
        public ?PlainDate $previewDate,
        public array $documents,
    ) {
    }
}
