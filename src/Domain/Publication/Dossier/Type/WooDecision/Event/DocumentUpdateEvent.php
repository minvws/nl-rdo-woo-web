<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Event;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Document;
use App\Service\Inventory\DocumentMetadata;

readonly class DocumentUpdateEvent
{
    public function __construct(
        public WooDecision $dossier,
        public DocumentMetadata $update,
        public Document $document,
    ) {
    }
}
