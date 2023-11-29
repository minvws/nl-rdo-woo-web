<?php

declare(strict_types=1);

namespace App\Service\Inventory\Reader;

use App\Entity\Dossier;

interface InventoryReaderInterface
{
    /**
     * @throws \Exception
     */
    public function open(string $filepath): void;

    /**
     * @return \Generator<InventoryReadItem>
     */
    public function getDocumentMetadataGenerator(Dossier $dossier): \Generator;

    public function getCount(): int;
}
