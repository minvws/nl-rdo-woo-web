<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer\DataProvider;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Inventory\Sanitizer\InventoryDocumentUrlStrategyInterface;

interface InventoryDataProviderInterface
{
    /**
     * @return array<array-key, Document>
     */
    public function getDocuments(): iterable;

    public function getDossierForDocument(Document $document): WooDecision;

    public function getDocumentUrlStrategy(): InventoryDocumentUrlStrategyInterface;

    public function getInventoryEntity(): EntityWithFileInfo;

    public function getFilename(): string;
}
