<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Event;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;

readonly class CovenantAttachmentUpdatedEvent
{
    public function __construct(
        public CovenantAttachment $attachment,
    ) {
    }
}
