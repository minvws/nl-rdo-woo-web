<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument\Command;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use Symfony\Component\Uid\Uuid;

readonly class UpdateMainDocumentCommand
{
    /**
     * @param array<array-key,string> $grounds
     */
    public function __construct(
        public Uuid $dossierId,
        public ?\DateTimeImmutable $formalDate,
        public ?string $internalReference,
        public ?AttachmentType $type,
        public ?AttachmentLanguage $language,
        public ?array $grounds,
        public ?string $uploadFileReference,
    ) {
    }
}
