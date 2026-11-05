<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use Exception;

final class SpreadsheetCannotBeOpenedException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.opening_inventory_file';

    public function __construct(Exception $exception)
    {
        parent::__construct('Error while opening the spreadsheet', previous: $exception);
    }

    public function getMessageKey(): string
    {
        return SpreadsheetCannotBeOpenedException::MESSAGE_KEY;
    }
}
