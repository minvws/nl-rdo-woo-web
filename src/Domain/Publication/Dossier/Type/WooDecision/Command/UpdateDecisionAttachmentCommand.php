<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Command;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use Symfony\Component\Uid\Uuid;

readonly class UpdateDecisionAttachmentCommand
{
    /**
     * @param string[] $grounds
     */
    public function __construct(
        public Uuid $dossierId,
        public Uuid $decisionAttachmentId,
        public ?\DateTimeImmutable $formalDate = null,
        public ?string $internalReference = null,
        public ?AttachmentType $type = null,
        public ?AttachmentLanguage $language = null,
        public ?array $grounds = null,
        public ?string $name = null,
        public ?string $uploadFileReference = null,
    ) {
    }
}
