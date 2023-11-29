<?php

declare(strict_types=1);

namespace App\Service\Excel;

use App\Exception\ExcelReaderException;

/**
 * This class provides a lookup of cell coordinate by mapping.
 * It is the result of applying the ColumnMapping(s) input in ExcelReaderFactory, and used by the ExcelReader instance for data lookups.
 */
class HeaderMap
{
    /**
     * @param array<string, string> $mapping
     */
    public function __construct(
        private readonly array $mapping,
    ) {
    }

    public function getCellCoordinate(string $headerName): string
    {
        if (! $this->has($headerName)) {
            throw ExcelReaderException::forUnknownHeader($headerName);
        }

        return $this->mapping[$headerName];
    }

    public function has(string $headerName): bool
    {
        return array_key_exists($headerName, $this->mapping);
    }
}
