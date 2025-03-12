<?php

declare(strict_types=1);

namespace App\Service\Inventory\Sanitizer\DataProvider;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\EntityWithFileInfo;

interface InventoryDataProviderInterface
{
    /**
     * @return array<array-key, Document>
     */
    public function getDocuments(): iterable;

    public function getInventoryEntity(): EntityWithFileInfo;

    public function getFilename(): string;
}
