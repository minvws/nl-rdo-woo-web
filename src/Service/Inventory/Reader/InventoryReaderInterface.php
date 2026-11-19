<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Reader;

use Exception;
use Generator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

interface InventoryReaderInterface
{
    /**
     * @param resource $stream
     *
     * @throws Exception
     */
    public function open($stream): void;

    /**
     * @return Generator<InventoryReadItem>
     */
    public function getDocumentMetadataGenerator(WooDecision $dossier): Generator;

    public function getCount(): int;

    public function hasMatterColumn(): bool;
}
