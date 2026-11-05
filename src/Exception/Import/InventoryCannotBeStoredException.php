<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class InventoryCannotBeStoredException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.can_not_store_inventory_file';

    public function __construct()
    {
        parent::__construct('The inventory file could not be stored');
    }

    public function getMessageKey(): string
    {
        return InventoryCannotBeStoredException::MESSAGE_KEY;
    }
}
