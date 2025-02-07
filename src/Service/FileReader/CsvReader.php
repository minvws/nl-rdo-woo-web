<?php

declare(strict_types=1);

namespace App\Service\FileReader;

use App\Exception\FileReaderException;
use App\Service\Inventory\InventoryDataHelper;

/**
 * CSV reader. This class will read the CSV file in its entirety and store it in memory.
 */
class CsvReader implements FileReaderInterface
{
    protected readonly string $filepath;
    protected readonly HeaderMap $mapping;
    /** @var mixed[][] */
    protected array $rows = [];

    public function __construct(string $filepath, HeaderMap $mapping)
    {
        $this->filepath = $filepath;
        $this->mapping = $mapping;

        $handle = fopen($this->filepath, 'r');
        if (! $handle) {
            throw new \RuntimeException('Failed to open file: ' . $this->filepath);
        }
        // read/skip the header row
        fgetcsv($handle);

        // Ensure a 1-indexed array
        $this->rows[0] = [];

        while (($data = fgetcsv($handle)) !== false) {
            $tmp = array_filter($data);
            if (count($tmp) == 0) {
                // Skip if all fields are empty
                continue;
            }
            $this->rows[] = $data;
        }
        fclose($handle);

        // Remove the dummy entry that was only there to create a 1-indexed array
        unset($this->rows[0]);
    }

    public function getIterator(): \Generator
    {
        foreach ($this->rows as $idx => $row) {
            yield $idx => $row;
        }
    }

    public function getCell(int $rowIndex, string $columnName): mixed
    {
        if (! $this->mapping->has($columnName)) {
            return null;
        }

        $col = $this->mapping->getCellCoordinate($columnName);

        return $this->rows[$rowIndex][$col] ?? null;
    }

    public function getString(int $rowIndex, string $columnName): string
    {
        return strval($this->getCell($rowIndex, $columnName));
    }

    public function getInt(int $rowIndex, string $columnName): int
    {
        return intval($this->getCell($rowIndex, $columnName));
    }

    public function getDateTime(int $rowIndex, string $columnName): \DateTimeImmutable
    {
        $value = $this->getString($rowIndex, $columnName);

        try {
            return InventoryDataHelper::toDateTimeImmutable($value);
        } catch (\Exception) {
            throw FileReaderException::forCannotParseDate($value);
        }
    }

    public function getOptionalDateTime(int $rowIndex, string $columnName): ?\DateTimeImmutable
    {
        $value = $this->getOptionalString($rowIndex, $columnName);
        if (empty($value)) {
            return null;
        }

        try {
            return InventoryDataHelper::toDateTimeImmutable($value);
        } catch (\Exception) {
            throw FileReaderException::forCannotParseDate($value);
        }
    }

    public function getCount(): int
    {
        return count($this->rows);
    }

    public function getOptionalString(int $rowIndex, string $columnName): ?string
    {
        $value = $this->getCell($rowIndex, $columnName);
        if ($value === null) {
            return null;
        }

        return strval($value);
    }

    public function getOptionalInt(int $rowIndex, string $columnName): ?int
    {
        $value = $this->getCell($rowIndex, $columnName);
        if ($value === null) {
            return null;
        }

        return intval($value);
    }
}
