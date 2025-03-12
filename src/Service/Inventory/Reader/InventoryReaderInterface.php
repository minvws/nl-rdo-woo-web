<?php

declare(strict_types=1);

namespace App\Service\Inventory\Reader;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

interface InventoryReaderInterface
{
    /**
     * @throws \Exception
     */
    public function open(string $filepath): void;

    /**
     * @return \Generator<InventoryReadItem>
     */
    public function getDocumentMetadataGenerator(WooDecision $dossier): \Generator;

    public function getCount(): int;
}
