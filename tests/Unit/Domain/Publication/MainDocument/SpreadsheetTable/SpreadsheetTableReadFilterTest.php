<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\MainDocument\SpreadsheetTable;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableReadFilter;
use Shared\Tests\Unit\UnitTestCase;

final class SpreadsheetTableReadFilterTest extends UnitTestCase
{
    public function testReadCell(): void
    {
        $maxRow = $this->getFaker()->numberBetween(10, 20);
        $maxColumn = $this->getFaker()->numberBetween(3, 10);
        $row = $this->getFaker()->numberBetween(1, 9);
        $column = Coordinate::stringFromColumnIndex($this->getFaker()->numberBetween(1, 3));

        $spreadsheetTableReadFilter = new SpreadsheetTableReadFilter($maxRow, $maxColumn);

        self::assertTrue($spreadsheetTableReadFilter->readCell($column, $row));
    }

    public function testReadCellAboveMaxRowCount(): void
    {
        $maxRow = $this->getFaker()->numberBetween(1, 10);
        $row = $this->getFaker()->numberBetween(11, 20);

        $spreadsheetTableReadFilter = new SpreadsheetTableReadFilter($maxRow, $this->getFaker()->numberBetween(3, 10));

        self::assertFalse($spreadsheetTableReadFilter->readCell('A', $row));
    }

    public function testReadCellAboveMaxColumnCount(): void
    {
        $maxColumn = $this->getFaker()->numberBetween(1, 3);
        $column = Coordinate::stringFromColumnIndex($this->getFaker()->numberBetween(4, 10));

        $spreadsheetTableReadFilter = new SpreadsheetTableReadFilter($this->getFaker()->numberBetween(10, 20), $maxColumn);

        self::assertFalse($spreadsheetTableReadFilter->readCell($column, 1));
    }
}
