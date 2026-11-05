<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;

final class UnknownHeaderException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.reading_cell';

    public function __construct(public readonly string $headerName)
    {
        parent::__construct(sprintf('The header "%s" does not exist in the spreadsheet', $headerName));
    }

    public function getMessageKey(): string
    {
        return UnknownHeaderException::MESSAGE_KEY;
    }
}
