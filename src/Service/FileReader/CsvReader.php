<?php

declare(strict_types=1);

namespace Shared\Service\FileReader;

use DateTimeImmutable;
use Exception;
use Generator;
use RuntimeException;
use Shared\Exception\FileReaderException;
use Shared\Service\Inventory\InventoryDataHelper;
use Webmozart\Assert\Assert;

use function array_filter;
use function count;
use function fclose;
use function fgetcsv;
use function fopen;
use function intval;
use function strval;

/**
 * CSV reader. This class will read the CSV file in its entirety and store it in memory.
 */
class CsvReader implements FileReaderInterface
{
    /** @var array<int,array<array-key,mixed>> */
    protected array $rows = [];

    public function __construct(protected readonly string $filepath, protected readonly HeaderMap $mapping)
    {
        $handle = fopen($this->filepath, 'r');
        if (! $handle) {
            throw new RuntimeException('Failed to open file: ' . $this->filepath);
        }
        // read/skip the header row
        fgetcsv($handle, escape: '\\');

        // Ensure a 1-indexed array
        $this->rows[0] = [];

        while (($data = fgetcsv($handle, escape: '\\')) !== false) {
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

    public function getIterator(): Generator
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
            throw FileReaderException::forCannotParseDate($value);
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
            throw FileReaderException::forCannotParseDate($value);
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
