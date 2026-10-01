<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Document;

use ApiPlatform\Metadata\ApiProperty;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Ground;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Validator\AllowedFileExtension;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\PublicationContext;
use Symfony\Component\Validator\Constraints as Assert;

class WooDecisionDocumentRequestDto
{
    /**
     * @param array<array-key, string> $inquiryNumbers
     * @param list<Ground> $grounds
     * @param array<array-key, string> $links
     * @param array<array-key, ExternalId> $refersTo
     */
    public function __construct(
        #[ApiProperty(description: 'The case numbers of the Woo requests this document is linked to.')]
        public array $inquiryNumbers,
        #[ApiProperty(description: 'The date of the document itself, not the publication date (format YYYY-MM-DD).')]
        public PlainDate $documentDate,
        #[ApiProperty(description: 'The identifier of the document within the publicationContext. Letters, digits, '
            . 'dots and hyphens only, maximum 170 characters.')]
        public DocumentId $documentId,
        #[ApiProperty(description: 'The external identifier of the document, unique within the organisation (max. 128 characters).')]
        public ExternalId $externalId,
        #[ApiProperty(description: 'Groups related documents, for example an email with attachments, that belong to the same family.')]
        public ?int $familyId,
        #[AllowedFileExtension(UploadGroupId::API_WOO_DECISION_DOCUMENTS)]
        #[ApiProperty(description: 'The file name of the document, including extension.')]
        public FileName $fileName,
        #[ApiProperty(description: 'The Woo exemption grounds applied to this document.')]
        public array $grounds,
        #[ApiProperty(description: 'Indicates whether publication of this document is temporarily suspended. '
            . 'Suspended documents are not uploaded or published.')]
        public bool $isSuspended,
        #[ApiProperty(description: 'The judgement made on the document: public, partially public, not public or already public.')]
        public Judgement $judgement,
        #[Assert\All([
            new Assert\Url(requireTld: true),
        ])]
        #[ApiProperty(description: 'References (URLs) associated with this document.')]
        public array $links,
        #[ApiProperty(schema: [
            'description' => 'The externalIds of other documents this document refers to. A document cannot refer to '
                . 'itself and referenced documents must exist.',
            'type' => 'array',
            'items' => [
                'type' => 'string',
                'format' => 'external-id',
            ],
        ])]
        public array $refersTo,
        #[ApiProperty(description: 'A free-text remark on the document.')]
        public ?string $remark,
        #[ApiProperty(description: 'The source type of the document, for example pdf, email, image or video.')]
        public SourceType $sourceType,
        #[ApiProperty(description: 'Groups documents that belong to the same email thread.')]
        public ?int $threadId,
        #[ApiProperty(
            description: 'The context within which this document was supplied, for example a batch or '
            . 'production identifier from the source system, e.g. `pub-context-1` (1 to 255 characters; letters, digits, '
            . 'dots, hyphens, underscores and tildes only). Together with documentId this forms the document number.',
            openapiContext: [
                'type' => 'string',
                'pattern' => PublicationContext::OPENAPI_PATTERN,
                'minLength' => PublicationContext::MIN_LENGTH,
                'maxLength' => PublicationContext::MAX_LENGTH,
                'example' => 'pub-context-1',
            ],
        )]
        public PublicationContext $publicationContext,
    ) {
    }
}
