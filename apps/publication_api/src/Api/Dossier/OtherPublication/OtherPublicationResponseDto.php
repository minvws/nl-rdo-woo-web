<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

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

final class OtherPublicationResponseDto implements DossierResponseDtoInterface
{
    /**
     * @param list<AttachmentResponseDto> $attachments
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
        #[ApiProperty(description: 'A summary of the other publication.')]
        public string $summary,
        #[ApiProperty(description: 'The subject this publication is categorised under, if applicable.')]
        public ?SubjectResponseDto $subject,
        #[ApiProperty(description: 'The department responsible for this publication.')]
        public DepartmentResponseDto $department,
        #[ApiProperty(description: 'The publication date of the other publication. Automatically set to the current '
            . 'date once the status becomes "published".')]
        public ?PlainDate $publicationDate,
        #[ApiProperty(description: 'The current status of the dossier in the publication process.')]
        public DossierStatus $status,
        #[ApiProperty(description: 'The main document of the other publication.')]
        public ?OtherPublicationMainDocumentResponseDto $mainDocument,
        #[ApiProperty(description: 'A notice explaining why (part of) this dossier is not public, provided instead of '
            . '`mainDocument`. Shown on the public page in place of the document, citing the applicable exemption '
            . 'grounds.')]
        public ?NoticeNotPublicResponseDto $noticeNotPublic,
        #[ApiProperty(description: 'The attachments that belong to this other publication.')]
        public array $attachments,
        #[ApiProperty(description: 'The date of the other publication.')]
        public PlainDate $dossierDate,
        #[SerializedName('_links')]
        #[ApiProperty(description: 'HAL links associated with this dossier: `self` (this resource) and, once the '
            . 'dossier is published, `public` (the dossier\'s public page).')]
        public LinkCollection $halLinks,
    ) {
    }
}
