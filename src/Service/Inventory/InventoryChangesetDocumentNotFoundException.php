<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use OutOfBoundsException;

use function sprintf;

final class InventoryChangesetDocumentNotFoundException extends OutOfBoundsException
{
    public function __construct(public readonly string $documentNumber)
    {
        parent::__construct(sprintf('documentNumber %s not found in InventoryChangeset', $documentNumber));
    }
}
