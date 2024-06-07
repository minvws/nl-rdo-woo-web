<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Command;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use Symfony\Component\Uid\Uuid;

readonly class CreateAttachmentCommand
{
    /**
     * @param array<array-key,string> $grounds
     */
    public function __construct(
        public Uuid $dossierId,
        public \DateTimeImmutable $formalDate,
        public string $internalReference,
        public AttachmentType $type,
        public AttachmentLanguage $language,
        public array $grounds,
        public string $uploadFileReference,
        public string $name,
    ) {
    }
}
