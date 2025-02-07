<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Event;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
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
