<?php

declare(strict_types=1);

namespace PublicationApi\Api\Attachment;

use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Citation;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Validator\AllowedFileExtension;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

class AttachmentRequestDto
{
    /**
     * @param array<array-key,string> $grounds
     */
    public function __construct(
        #[AllowedFileExtension(UploadGroupId::ATTACHMENTS)]
        public FileName $fileName,
        public PlainDate $formalDate,
        public AttachmentLanguage $language,
        public AttachmentType $type,
        public ExternalId $externalId,
        #[Assert\All([
            new Assert\Choice(choices: Citation::ALL_GROUND_KEYS),
        ])]
        public array $grounds = [],
    ) {
    }
}
