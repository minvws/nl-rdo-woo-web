<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Event;

use App\Entity\DecisionAttachment;
use App\Entity\Dossier;

readonly class DecisionAttachmentDeletedEvent
{
    public function __construct(
        public Dossier $dossier,
        public DecisionAttachment $decisionAttachment,
    ) {
    }
}
