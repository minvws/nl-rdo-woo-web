<?php

declare(strict_types=1);

namespace PublicationApi\Api\Attachment;

use ApiPlatform\Metadata\ApiProperty;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Ground;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Validator\AllowedFileExtension;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;

class AttachmentRequestDto
{
    /**
     * @param list<Ground> $grounds
     */
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
        #[ApiProperty(description: 'The Woo exemption grounds applied to the attachment.')]
        public array $grounds = [],
    ) {
    }
}
