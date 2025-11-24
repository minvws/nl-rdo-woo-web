<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer\DataProvider;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\EntityWithFileInfo;

interface InventoryDataProviderInterface
{
    /**
     * @return array<array-key, Document>
     */
    public function getDocuments(): iterable;

    public function getInventoryEntity(): EntityWithFileInfo;

    public function getFilename(): string;
}
