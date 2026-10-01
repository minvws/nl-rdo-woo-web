<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

interface InventoryDocumentUrlStrategyInterface
{
    public function generateDocumentUrl(Document $document, WooDecision $inventoryDossier): string;

    public function generateRelatedDocumentUrl(Document $document, WooDecision $inventoryDossier): string;
}
