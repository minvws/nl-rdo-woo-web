<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\SpreadsheetTable;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

final readonly class SpreadsheetTableReadFilter implements IReadFilter
{
    public function __construct(
        private int $maxRow,
        private int $maxColumn,
    ) {
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        if ($row > $this->maxRow) {
            return false;
        }

        return Coordinate::columnIndexFromString($columnAddress) <= $this->maxColumn;
    }
}
