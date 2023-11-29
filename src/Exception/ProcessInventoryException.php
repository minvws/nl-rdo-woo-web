<?php

declare(strict_types=1);

namespace App\Exception;

class ProcessInventoryException extends TranslatableException
{
    public static function forInventoryCannotBeStored(): self
    {
        return new self('Cannot store the inventory spreadsheet');
    }

    public static function forInventoryCannotBeLoadedFromStorage(): self
    {
        return new self('Could not download the inventory from document storage');
    }

    public static function forSanitizerException(InventorySanitizerException $exception): self
    {
        return new self(
            'Error while generating sanitized inventory: ' . $exception->getMessage(),
            'Error while generating sanitized inventory',
        );
    }

    public static function forMissingDocument(string $documentNr): self
    {
        return new self(
            sprintf('Missing document %s in the inventory', $documentNr),
            'Missing document {documentNumber} in the inventory',
            [
                '{documentNumber}' => $documentNr,
            ]
        );
    }

    public static function forOtherException(\Exception $exception): self
    {
        return new self(
            sprintf('Uncaught exception during inventory processing: %s', $exception->getMessage()),
            'Exception occurred during inventory processing',
        );
    }

    public static function forNoChanges(): self
    {
        return new self('Inventory file has no changes');
    }

    public static function forMaxRuntimeExceeded(): self
    {
        return new self('Inventory processing exceeded maximum runtime');
    }
}
