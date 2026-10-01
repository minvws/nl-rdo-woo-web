<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use ApiPlatform\Metadata\ApiProperty;
use PublicationApi\Api\Attachment\AttachmentResponseDto;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

/**
 * DraftDecision attachments never have grounds: their documents may not be redacted.
 * This DTO therefore omits the grounds property that the shared AttachmentResponseDto exposes.
 */
final readonly class DraftDecisionAttachmentResponseDto
{
    public function __construct(
        #[ApiProperty(description: 'The unique identifier of the attachment.')]
        public Uuid $id,
        #[ApiProperty(description: 'The document type of the attachment.')]
        public AttachmentType $type,
        #[ApiProperty(description: 'The language of the attachment.')]
        public AttachmentLanguage $language,
        #[ApiProperty(description: 'The formal date of the attachment (format YYYY-MM-DD).')]
        public PlainDate $formalDate,
        #[ApiProperty(description: 'The file name of the uploaded attachment, if available.')]
        public ?string $fileName,
        #[ApiProperty(description: 'The external identifier of the attachment, unique within the dossier.')]
        public ?ExternalId $externalId,
        #[ApiProperty(description: 'The status of the attachment\'s upload process.')]
        public UploadStatus $uploadStatus,
        #[SerializedName('_links')]
        #[ApiProperty(description: 'HAL links associated with the attachment: `upload` (where to upload the file) '
            . 'and, once the dossier is published, `public` (the attachment\'s public page) and `file` (a direct '
            . 'download link).')]
        public LinkCollection $halLinks,
    ) {
    }

    public static function fromAttachmentResponseDto(AttachmentResponseDto $attachmentResponseDto): self
    {
        return new self(
            $attachmentResponseDto->id,
            $attachmentResponseDto->type,
            $attachmentResponseDto->language,
            $attachmentResponseDto->formalDate,
            $attachmentResponseDto->fileName,
            $attachmentResponseDto->externalId,
            $attachmentResponseDto->uploadStatus,
            $attachmentResponseDto->halLinks,
        );
    }
}
