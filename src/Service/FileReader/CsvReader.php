<?php

declare(strict_types=1);

namespace Shared\Service\FileReader;

use DateTimeImmutable;
use Exception;
use Generator;
use Shared\Exception\Import\DateCannotBeParsedException;
use Shared\Service\Inventory\InventoryDataHelper;
use Webmozart\Assert\Assert;

use function array_filter;
use function count;
use function fgetcsv;
use function intval;
use function strval;

/**
 * CSV reader. This class will read the CSV file in its entirety and store it in memory.
 */
class CsvReader implements FileReaderInterface
{
    /** @var array<int, array<array-key,mixed>> */
    protected array $rows = [];

    /**
     * @param resource $stream
     */
    public function __construct($stream, protected readonly HeaderMap $mapping)
    {
        // read/skip the header row
        fgetcsv($stream, escape: '\\');

        // Ensure a 1-indexed array
        $this->rows[0] = [];

        while (($data = fgetcsv($stream, escape: '\\')) !== false) {
            $tmp = array_filter($data);
            if (count($tmp) == 0) {
                // Skip if all fields are empty
                continue;
            }
            $this->rows[] = $data;
        }

        // Remove the dummy entry that was only there to create a 1-indexed array
        unset($this->rows[0]);
    }

    /** @return Generator<int, array<array-key, mixed>> */
    public function getIterator(): Generator
    {
        foreach ($this->rows as $idx => $row) {
            yield $idx => $row;
        }
    }

    public function hasColumn(string $columnName): bool
    {
        return $this->mapping->has($columnName);
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
        $cell = $this->getCell($rowIndex, $columnName);
        Assert::nullOrScalar($cell);

        return strval($cell);
    }

    public function getInt(int $rowIndex, string $columnName): int
    {
        $cell = $this->getCell($rowIndex, $columnName);
        Assert::nullOrScalar($cell);

        return intval($cell);
    }

    public function getDateTime(int $rowIndex, string $columnName): DateTimeImmutable
    {
        $value = $this->getString($rowIndex, $columnName);

        try {
            return InventoryDataHelper::toDateTimeImmutable($value);
        } catch (Exception) {
            throw new DateCannotBeParsedException($value);
        }
    }

    public function getOptionalDateTime(int $rowIndex, string $columnName): ?DateTimeImmutable
    {
        $value = $this->getOptionalString($rowIndex, $columnName);
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return InventoryDataHelper::toDateTimeImmutable($value);
        } catch (Exception) {
            throw new DateCannotBeParsedException($value);
        }
    }

    public function getCount(): int
    {
        return count($this->rows);
    }

    public function getOptionalString(int $rowIndex, string $columnName): ?string
    {
        $cell = $this->getCell($rowIndex, $columnName);
        Assert::nullOrScalar($cell);

        if ($cell === null) {
            return null;
        }

        return strval($cell);
    }

    public function getOptionalInt(int $rowIndex, string $columnName): ?int
    {
        $cell = $this->getCell($rowIndex, $columnName);
        Assert::nullOrScalar($cell);

        if ($cell === null || $cell === '') {
            return null;
        }

        return intval($cell);
    }
}
