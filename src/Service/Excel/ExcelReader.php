<?php

declare(strict_types=1);

namespace App\Service\Excel;

use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Wraps Worksheet to provide some helper methods to make it easier to use.
 * Also skips empty rows silently.
 *
 * @template-implements \IteratorAggregate<Row>
 */
class ExcelReader implements \IteratorAggregate
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

            yield $row;
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
        return new \DateTimeImmutable($this->getString($rowIndex, $columnName));
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
}
