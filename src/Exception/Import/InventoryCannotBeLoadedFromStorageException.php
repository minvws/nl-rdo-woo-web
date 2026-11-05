<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class InventoryCannotBeLoadedFromStorageException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.could_not_download_inventory';

    public function __construct()
    {
        parent::__construct('The inventory file could not be loaded from storage');
    }

    public function getMessageKey(): string
    {
        return InventoryCannotBeLoadedFromStorageException::MESSAGE_KEY;
    }
}
