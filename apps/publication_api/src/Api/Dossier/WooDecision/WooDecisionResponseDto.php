<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use ApiPlatform\Metadata\ApiProperty;
use PublicationApi\Api\Attachment\AttachmentResponseDto;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\DossierResponseDtoInterface;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentResponseDto;
use PublicationApi\Api\Organisation\OrganisationResponseDto;
use PublicationApi\Api\Subject\SubjectResponseDto;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

final class WooDecisionResponseDto implements DossierResponseDtoInterface
{
    /**
     * @param list<AttachmentResponseDto> $attachments
     * @param list<WooDecisionDocumentResponseDto> $documents
     */
    final public function __construct(
        #[ApiProperty(description: 'The unique identifier of the dossier.')]
        public Uuid $id,
        #[ApiProperty(description: 'The external identifier of the dossier, as supplied by the source system.')]
        public ?ExternalId $externalId,
        #[ApiProperty(description: 'The organisation this dossier belongs to.')]
        public OrganisationResponseDto $organisation,
        #[ApiProperty(description: 'The dossier number this publication is registered under.')]
        public string $dossierNumber,
        #[ApiProperty(description: 'The title of the dossier.')]
        public DossierTitle $title,
        #[ApiProperty(description: 'A summary of the decision.')]
        public string $summary,
        #[ApiProperty(description: 'The subject this publication is categorised under, if applicable.')]
        public ?SubjectResponseDto $subject,
        #[ApiProperty(description: 'The department responsible for this publication.')]
        public DepartmentResponseDto $department,
        #[ApiProperty(description: 'The publication date of the decision. Automatically set to the current date once '
            . 'the status becomes "published".')]
        public ?PlainDate $publicationDate,
        #[ApiProperty(description: 'The current status of the dossier in the publication process.')]
        public DossierStatus $status,
        #[ApiProperty(description: 'The main document of the decision, for example the decision letter.')]
        public WooDecisionMainDocumentResponseDto $mainDocument,
        #[ApiProperty(description: 'The attachments that belong to this decision.')]
        public array $attachments,
        #[ApiProperty(description: 'Start date of the period the documents in this decision relate to.')]
        public ?PlainDate $dateFrom,
        #[ApiProperty(description: 'End date of the period the documents in this decision relate to.')]
        public ?PlainDate $dateTo,
        #[ApiProperty(description: 'The type of decision, for example public, partially public, not public or already public.')]
        public ?DecisionType $decision,
        #[ApiProperty(description: 'The legal basis of the request: a Wob or a Woo request.')]
        public PublicationReason $reason,
        #[ApiProperty(description: 'The date on which the publication is made available as a preview, ahead of the publication date.')]
        public ?PlainDate $previewDate,
        #[ApiProperty(description: 'The documents that are part of this decision.')]
        public array $documents,
        #[SerializedName('_links')]
        #[ApiProperty(description: 'HAL links associated with this dossier: `self` (this resource) and, once the '
            . 'dossier is published, `public` (the dossier\'s public page).')]
        public LinkCollection $halLinks,
    ) {
    }
}
