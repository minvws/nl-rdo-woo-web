<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\Command;

use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

readonly class UpdateMainDocumentCommand
{
    /**
     * @param array<array-key,string> $grounds
     */
    public function __construct(
        public Uuid $dossierId,
        public ?PlainDate $formalDate = null,
        public ?string $internalReference = null,
        public ?AttachmentType $type = null,
        public ?AttachmentLanguage $language = null,
        public ?array $grounds = null,
        public ?string $uploadFileReference = null,
    ) {
    }
}
