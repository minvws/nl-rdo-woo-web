<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Document;

use ApiPlatform\Metadata\ApiProperty;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\SourceType;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class WooDecisionDocumentResponseDto
{
    /**
     * @param list<string> $inquiryNumbers
     * @param list<string> $grounds
     * @param list<string> $links
     * @param list<WooDecisionRelatedDocumentResponseDto> $refersTo
     */
    public function __construct(
        #[ApiProperty(description: 'The case numbers of the Woo requests this document is linked to.')]
        public array $inquiryNumbers,
        #[ApiProperty(description: 'The date of the document itself, not the publication date (format YYYY-MM-DD).')]
        public ?PlainDate $documentDate,
        #[ApiProperty(description: 'The identifier of the document within the publicationContext.')]
        public DocumentId $documentId,
        #[ApiProperty(description: 'The full document number, composed of the publicationContext and the documentId.')]
        public DocumentNumber $documentNumber,
        #[ApiProperty(description: 'The external identifier of the document, unique within the organisation.')]
        public ?ExternalId $externalId,
        #[ApiProperty(description: 'Groups related documents, for example an email with attachments, that belong to the same family.')]
        public ?int $familyId,
        #[ApiProperty(description: 'The file name of the uploaded document, if available.')]
        public ?string $fileName,
        #[ApiProperty(description: 'The source type of the document, for example pdf, email, image or video.')]
        public ?SourceType $sourceType,
        #[ApiProperty(description: 'The Woo exemption grounds applied to this document.')]
        public array $grounds,
        #[ApiProperty(description: 'Indicates whether publication of this document is temporarily suspended.')]
        public bool $isSuspended,
        #[ApiProperty(description: 'Indicates whether the file for this document has actually been uploaded.')]
        public bool $isUploaded,
        #[ApiProperty(description: 'Indicates whether this document has been withdrawn.')]
        public bool $isWithdrawn,
        #[ApiProperty(description: 'The judgement made on the document: public, partially public, not public or already public.')]
        public ?Judgement $judgement,
        #[ApiProperty(description: 'References (URLs) associated with this document.')]
        public array $links,
        #[ApiProperty(description: 'The documents this document refers to.')]
        public array $refersTo,
        #[ApiProperty(description: 'A free-text remark on the document.')]
        public ?string $remark,
        #[ApiProperty(description: 'Groups documents that belong to the same email thread.')]
        public ?int $threadId,
        #[ApiProperty(description: 'The status of the document\'s upload process.')]
        public UploadStatus $uploadStatus,
        #[SerializedName('_links')]
        #[ApiProperty(description: 'HAL links associated with the document: `upload` (where to upload the file), '
            . '`inquiries` (the Woo requests this document is linked to) and, once the dossier is published, `public` '
            . '(the document\'s public page) and `file` (a direct download link).')]
        public LinkCollection $halLinks,
    ) {
    }
}
