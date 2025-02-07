<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Command;

use Symfony\Component\Uid\Uuid;

readonly class DeleteAttachmentCommand
{
    public function __construct(
        public Uuid $dossierId,
        public Uuid $attachmentId,
    ) {
    }
}
