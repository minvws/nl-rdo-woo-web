<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Command;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use Symfony\Component\Uid\Uuid;

readonly class UpdateAttachmentCommand
{
    /**
     * @param array<array-key,string> $grounds
     */
    public function __construct(
        public Uuid $dossierId,
        public Uuid $attachmentId,
        public ?\DateTimeImmutable $formalDate = null,
        public ?string $internalReference = null,
        public ?AttachmentType $type = null,
        public ?AttachmentLanguage $language = null,
        public ?array $grounds = null,
        public ?string $uploadFileReference = null,
        public ?string $name = null,
    ) {
    }
}
