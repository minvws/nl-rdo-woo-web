<?php

declare(strict_types=1);

namespace Shared\Exception;

use Exception;

use function implode;
use function sprintf;
use function strval;

class FileReaderException extends TranslatableException
{
    /**
     * @param string[] $missingHeaders
     */
    public static function forMissingHeaders(array $missingHeaders): self
    {
        $missing = implode(', ', $missingHeaders);

        return new self(
            "Could not find the correct headers in the spreadsheet. Missing: $missing",
            'publication.dossier.error.missing_inventory_header',
            [
                '{headername}' => $missing,
            ]
        );
    }

    public static function forRowProcessingException(int $rowIndex, Exception $exception): TranslatableException
    {
        if ($exception instanceof TranslatableException) {
            return $exception;
        }

        return new self(
            "Error while processing row $rowIndex in the spreadsheet: " . $exception->getMessage(),
            'publication.dossier.error.processing_inventory_row',
            [
                '{rowIndex}' => strval($rowIndex),
            ]
        );
    }

    public static function forOpenSpreadsheetException(Exception $exception): self
    {
        return new self(
            'Error while opening the spreadsheet: ' . $exception->getMessage(),
            'publication.dossier.error.opening_inventory_file',
        );
    }

    public static function forUnknownHeader(string $headerName): self
    {
        return new self(
            sprintf('The header "%s" does not exist in the spreadsheet', $headerName),
            'publication.dossier.error.reading_cell',
        );
    }

    public static function forCannotParseDate(string $date): self
    {
        return new self(
            "Date '$date' cannot be parsed",
            'date {date} cannot be parsed',
            [
                '{date}' => $date,
            ]
        );
    }
}
