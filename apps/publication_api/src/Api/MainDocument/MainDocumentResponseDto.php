<?php

declare(strict_types=1);

namespace PublicationApi\Api\MainDocument;

use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

final readonly class MainDocumentResponseDto
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        public Uuid $id,
        public AttachmentType $type,
        public AttachmentLanguage $language,
        public PlainDate $formalDate,
        public array $grounds,
        public ?string $fileName,
        public UploadStatus $uploadStatus,
    ) {
    }
}
