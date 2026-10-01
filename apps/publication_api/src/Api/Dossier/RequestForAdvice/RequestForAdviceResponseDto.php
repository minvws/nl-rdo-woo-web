<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

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

final class RequestForAdviceResponseDto implements DossierResponseDtoInterface
{
    /**
     * @param list<AttachmentResponseDto> $attachments
     * @param list<string> $advisoryBodies
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
        #[ApiProperty(description: 'A summary of the request for advice.')]
        public string $summary,
        #[ApiProperty(description: 'The subject this publication is categorised under, if applicable.')]
        public ?SubjectResponseDto $subject,
        #[ApiProperty(description: 'The department responsible for this publication.')]
        public DepartmentResponseDto $department,
        #[ApiProperty(description: 'The publication date of the request for advice. Automatically set to the current '
            . 'date once the status becomes "published".')]
        public ?PlainDate $publicationDate,
        #[ApiProperty(description: 'The current status of the dossier in the publication process.')]
        public DossierStatus $status,
        #[ApiProperty(description: 'The main document of the request for advice.')]
        public ?RequestForAdviceMainDocumentResponseDto $mainDocument,
        #[ApiProperty(description: 'A notice explaining why (part of) this dossier is not public, provided instead of '
            . '`mainDocument`. Shown on the public page in place of the document, citing the applicable exemption '
            . 'grounds.')]
        public ?NoticeNotPublicResponseDto $noticeNotPublic,
        #[ApiProperty(description: 'The attachments that belong to this request for advice.')]
        public array $attachments,
        #[ApiProperty(description: 'The date of the request for advice.')]
        public PlainDate $dossierDate,
        #[ApiProperty(description: 'A link to the advice that resulted from this request for advice. Must be a valid '
            . 'URL, e.g. `https://www.example.org/advice`.')]
        public string $link,
        #[ApiProperty(description: 'The advisory body that issued or will issue the advice.')]
        public array $advisoryBodies,
        #[SerializedName('_links')]
        #[ApiProperty(description: 'HAL links associated with this dossier: `self` (this resource) and, once the '
            . 'dossier is published, `public` (the dossier\'s public page).')]
        public LinkCollection $halLinks,
    ) {
    }
}
