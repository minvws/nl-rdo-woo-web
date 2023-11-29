<?php

declare(strict_types=1);

namespace App\Service\Inventory\Sanitizer;

use App\Entity\Document;
use App\Entity\EntityWithFileInfo;

interface InventoryDataProviderInterface
{
    /**
     * @return Document[]
     */
    public function getDocuments(): iterable;

    public function getInventoryEntity(): EntityWithFileInfo;

    public function getFilename(): string;
}
