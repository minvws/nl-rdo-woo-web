<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

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
        public Uuid $id,
        public AttachmentType $type,
        public AttachmentLanguage $language,
        public PlainDate $formalDate,
        public ?string $fileName,
        public ?ExternalId $externalId,
        public UploadStatus $uploadStatus,
        #[SerializedName('_links')]
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
