<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use ApiPlatform\Metadata\ApiProperty;
use PublicationApi\Api\Attachment\AttachmentResponseDto;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\DossierResponseDtoInterface;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDto;
use PublicationApi\Api\Organisation\OrganisationResponseDto;
use PublicationApi\Api\Subject\SubjectResponseDto;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

final class CovenantResponseDto implements DossierResponseDtoInterface
{
    /**
     * @param list<AttachmentResponseDto> $attachments
     * @param list<string> $parties
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
        #[ApiProperty(description: 'A summary of the covenant.')]
        public string $summary,
        #[ApiProperty(description: 'The subject this publication is categorised under, if applicable.')]
        public ?SubjectResponseDto $subject,
        #[ApiProperty(description: 'The department responsible for this publication.')]
        public DepartmentResponseDto $department,
        #[ApiProperty(description: 'The publication date of the covenant. Automatically set to the current date once '
            . 'the status becomes "published".')]
        public ?PlainDate $publicationDate,
        #[ApiProperty(description: 'The current status of the dossier in the publication process.')]
        public DossierStatus $status,
        #[ApiProperty(description: 'The main document of the covenant.')]
        public ?CovenantMainDocumentResponseDto $mainDocument,
        #[ApiProperty(description: 'A notice explaining why (part of) this dossier is not public, provided instead of '
            . '`mainDocument`. Shown on the public page in place of the document, citing the applicable exemption '
            . 'grounds.')]
        public ?NoticeNotPublicResponseDto $noticeNotPublic,
        #[ApiProperty(description: 'The attachments that belong to this covenant.')]
        public array $attachments,
        #[ApiProperty(description: 'Start date of the period the covenant relates to.')]
        public PlainDate $dateFrom,
        #[ApiProperty(description: 'End date of the period the covenant relates to.')]
        public ?PlainDate $dateTo,
        #[ApiProperty(description: 'A link to the previous version of this covenant, if this covenant supersedes an '
            . 'earlier one. Must be a valid URL, e.g. `https://www.example.org/covenant-2023`.')]
        public string $previousVersionLink,
        #[ApiProperty(description: 'The parties involved in the covenant.')]
        public array $parties,
        #[SerializedName('_links')]
        #[ApiProperty(description: 'HAL links associated with this dossier: `self` (this resource) and, once the '
            . 'dossier is published, `public` (the dossier\'s public page).')]
        public LinkCollection $halLinks,
    ) {
    }
}
