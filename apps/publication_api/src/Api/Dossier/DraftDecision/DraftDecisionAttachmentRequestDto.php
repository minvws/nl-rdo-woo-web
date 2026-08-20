<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

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
        public FileName $fileName,
        public PlainDate $formalDate,
        public AttachmentLanguage $language,
        public AttachmentType $type,
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
