<?php

declare(strict_types=1);

namespace App\Exception;

class InventoryReaderException extends \RuntimeException
{
    /**
     * @param string[] $missingHeaders
     */
    public static function forMissingHeaders(array $missingHeaders): self
    {
        $missing = implode(', ', $missingHeaders);

        return new self("Could not find the correct headers in the spreadsheet. Missing: $missing");
    }

    public static function forRowProcessingException(int $rowIndex, \Exception $exception): self
    {
        return new self("Error while processing row $rowIndex in the spreadsheet: " . $exception->getMessage());
    }

    public static function forMissingDocumentIdInRow(int $rowIndex): self
    {
        return new self("Missing document ID in inventory row #$rowIndex");
    }
}
