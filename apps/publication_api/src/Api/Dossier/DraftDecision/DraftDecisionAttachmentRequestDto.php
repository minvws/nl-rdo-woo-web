<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use ApiPlatform\Metadata\ApiProperty;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Validator\AllowedFileExtension;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;

/**
 * DraftDecision attachments never have grounds: their documents may not be redacted.
 * This DTO therefore omits the grounds property that the shared AttachmentRequestDto exposes.
 */
class DraftDecisionAttachmentRequestDto
{
    public function __construct(
        #[AllowedFileExtension(UploadGroupId::ATTACHMENTS)]
        #[ApiProperty(description: 'The file name of the attachment, including extension.')]
        public FileName $fileName,
        #[ApiProperty(description: 'The formal date of the attachment, for example the date it was adopted or signed (format YYYY-MM-DD).')]
        public PlainDate $formalDate,
        #[ApiProperty(description: 'The language of the attachment.')]
        public AttachmentLanguage $language,
        #[ApiProperty(description: 'The document type of the attachment.')]
        public AttachmentType $type,
        #[ApiProperty(description: 'The external identifier of the attachment, unique within the dossier (max. 128 characters).')]
        public ExternalId $externalId,
    ) {
    }

    public function toAttachmentRequestDto(): AttachmentRequestDto
    {
        return new AttachmentRequestDto(
            $this->fileName,
            $this->formalDate,
            $this->language,
            $this->type,
            $this->externalId,
            grounds: [],
        );
    }
}
