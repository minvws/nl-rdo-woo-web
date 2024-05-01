<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Command;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use Symfony\Component\Uid\Uuid;

readonly class UpdateCovenantDocumentCommand
{
    /**
     * @param string[] $grounds
     */
    public function __construct(
        public Uuid $dossierId,
        public ?\DateTimeImmutable $formalDate,
        public ?string $internalReference,
        public ?AttachmentLanguage $language,
        public ?array $grounds,
        public ?string $uploadFileReference,
        public ?string $name,
    ) {
    }
}
