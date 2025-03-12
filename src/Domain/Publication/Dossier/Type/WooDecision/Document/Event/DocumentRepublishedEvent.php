<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document\Event;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;

readonly class DocumentRepublishedEvent
{
    public function __construct(
        public Document $document,
    ) {
    }
}
