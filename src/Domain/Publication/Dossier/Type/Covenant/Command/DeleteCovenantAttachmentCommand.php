<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Command;

use Symfony\Component\Uid\Uuid;

readonly class DeleteCovenantAttachmentCommand
{
    public function __construct(
        public Uuid $dossierId,
        public Uuid $attachmentId,
    ) {
    }
}
