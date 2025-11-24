<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\DocumentMetadata;

readonly class DocumentUpdateEvent
{
    public function __construct(
        public WooDecision $dossier,
        public DocumentMetadata $update,
        public Document $document,
    ) {
    }
}
