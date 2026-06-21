<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use PublicationApi\Api\Attachment\AttachmentResponseDto;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentResponseDto;
use PublicationApi\Api\Organisation\OrganisationResponseDto;
use PublicationApi\Api\Subject\SubjectResponse;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Attribute\SerializedName;
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
        public DossierTitle $title,
        public string $summary,
        public ?SubjectResponse $subject,
        public DepartmentResponseDto $department,
        public ?PlainDate $publicationDate,
        public DossierStatus $status,
        public WooDecisionMainDocumentResponseDto $mainDocument,
        public array $attachments,
        public ?PlainDate $dateFrom,
        public ?PlainDate $dateTo,
        public ?DecisionType $decision,
        public PublicationReason $reason,
        public ?PlainDate $previewDate,
        public array $documents,
        #[SerializedName('_links')]
        public LinkCollection $halLinks,
    ) {
    }
}
