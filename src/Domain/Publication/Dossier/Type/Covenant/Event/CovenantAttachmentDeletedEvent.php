<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Event;

use Symfony\Component\Uid\Uuid;

readonly class CovenantAttachmentDeletedEvent
{
    public function __construct(
        public Uuid $dossierId,
        public Uuid $attachmentId,
    ) {
    }
}
