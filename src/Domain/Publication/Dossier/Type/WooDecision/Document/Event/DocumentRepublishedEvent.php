<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;

readonly class DocumentRepublishedEvent
{
    public function __construct(
        public Document $document,
    ) {
    }
}
