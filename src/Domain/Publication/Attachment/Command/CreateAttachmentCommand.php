<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Command;

use DateTimeImmutable;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Symfony\Component\Uid\Uuid;

readonly class CreateAttachmentCommand
{
    /**
     * @param array<array-key,string> $grounds
     */
    public function __construct(
        public Uuid $dossierId,
        public DateTimeImmutable $formalDate,
        public string $internalReference,
        public AttachmentType $type,
        public AttachmentLanguage $language,
        public array $grounds,
        public string $uploadFileReference,
    ) {
    }
}
