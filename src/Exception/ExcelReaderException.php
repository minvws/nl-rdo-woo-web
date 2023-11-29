<?php

declare(strict_types=1);

namespace App\Exception;

class ExcelReaderException extends TranslatableException
{
    /**
     * @param string[] $missingHeaders
     */
    public static function forMissingHeaders(array $missingHeaders): self
    {
        $missing = implode(', ', $missingHeaders);

        return new self(
            "Could not find the correct headers in the spreadsheet. Missing: $missing",
            'Missing inventory header {headername}',
            [
                '{headername}' => $missing,
            ]
        );
    }

    public static function forRowProcessingException(int $rowIndex, \Exception $exception): self
    {
        return new self(
            "Error while processing row $rowIndex in the spreadsheet: " . $exception->getMessage(),
            'Error while processing inventory row {rowIndex}',
            [
                '{rowIndex}' => strval($rowIndex),
            ]
        );
    }

    public static function forOpenSpreadsheetException(\Exception $exception): self
    {
        return new self(
            'Error while opening the spreadsheet: ' . $exception->getMessage(),
            'Error while opening the inventory spreadsheet',
        );
    }

    public static function forUnknownHeader(string $headerName): self
    {
        return new self(
            sprintf('The header "%s" does not exists in the spreadsheet', $headerName),
            'Error while reading spreadsheet cell',
        );
    }
}
