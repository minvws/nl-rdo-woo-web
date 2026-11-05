<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class NoInventoryChangesException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.no_inventory_changes';

    public function __construct()
    {
        parent::__construct('The inventory contains no changes');
    }

    public function getMessageKey(): string
    {
        return NoInventoryChangesException::MESSAGE_KEY;
    }
}
