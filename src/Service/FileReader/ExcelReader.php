<?php

declare(strict_types=1);

namespace App\Service\FileReader;

use App\Exception\FileReaderException;
use App\Service\Inventory\InventoryDataHelper;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Wraps Worksheet to provide some helper methods to make it easier to use.
 * Also skips empty rows silently.
 *
 * @template-implements \IteratorAggregate<Row>
 */
class ExcelReader implements \IteratorAggregate, FileReaderInterface
{
    public function __construct(
        private readonly Worksheet $worksheet,
        private readonly HeaderMap $headerMapping,
    ) {
    }

    /**
     * @return \Generator<Row>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->worksheet->getRowIterator(2) as $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            yield $row->getRowIndex() => $row;
        }
    }

    public function getCell(int $rowIndex, string $columnName): mixed
    {
        return $this->worksheet->getCell(
            $this->headerMapping->getCellCoordinate($columnName) . $rowIndex
        )->getValue();
    }

    public function getString(int $rowIndex, string $columnName): string
    {
        return strval($this->getCell($rowIndex, $columnName));
    }

    public function getOptionalString(int $rowIndex, string $columnName): ?string
    {
        return $this->hasColumn($columnName) ? $this->getString($rowIndex, $columnName) : null;
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

    private function hasColumn(string $headerName): bool
    {
        return $this->headerMapping->has($headerName);
    }

    private function isEmptyRow(Row $row): bool
    {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        foreach ($cellIterator as $cell) {
            $value = $cell->getValue();
            if ($value !== null && trim(strval($value)) !== '') {
                return false;
            }
        }

        return true;
    }

    public function getCount(): int
    {
        return $this->worksheet->getHighestRow();
    }

    public function getOptionalInt(int $rowIndex, string $columnName): ?int
    {
        return $this->hasColumn($columnName) ? $this->getInt($rowIndex, $columnName) : null;
    }
}
