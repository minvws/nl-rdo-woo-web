<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Event;

use App\Entity\DecisionAttachment;

final readonly class DecisionAttachmentCreatedEvent
{
    public function __construct(
        public DecisionAttachment $decisionAttachment,
    ) {
    }
}
