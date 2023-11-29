<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Excel;

use App\Service\Excel\ExcelReader;
use App\Service\Excel\HeaderMap;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelReaderTest extends MockeryTestCase
{
    private MockInterface|HeaderMap $headerMap;
    private ExcelReader $excelReader;

    public function setUp(): void
    {
        $headerMap = \Mockery::mock(HeaderMap::class);
        $headerMap->expects('has')->with('id')->zeroOrMoreTimes()->andReturnTrue();
        $headerMap->expects('getCellCoordinate')->with('id')->zeroOrMoreTimes()->andReturn('B');
        $headerMap->expects('has')->with('subject')->zeroOrMoreTimes()->andReturnTrue();
        $headerMap->expects('getCellCoordinate')->with('subject')->zeroOrMoreTimes()->andReturn('I');
        $headerMap->expects('has')->with('foobar')->zeroOrMoreTimes()->andReturnFalse();

        // The worksheet is unfortunately very hard to mock accurately
        $worksheet = IOFactory::load(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.xlsx');
        $this->excelReader = new ExcelReader($worksheet->getSheet(0), $headerMap);
    }

    public function testIteratorSkipsFirstRowAndEmptyRows(): void
    {
        $ids = [];
        foreach ($this->excelReader as $row) {
            $ids[] = $this->excelReader->getInt($row->getRowIndex(), 'id');
        }

        $this->assertEquals(
            [5033, 5034],
            $ids,
        );
    }

    public function testGetOptionalStringReturnsValueForExistingColumnWhenFilled(): void
    {
        $subjects = [];
        foreach ($this->excelReader as $row) {
            $subjects[] = $this->excelReader->getOptionalString($row->getRowIndex(), 'subject');
        }

        $this->assertEquals(
            ['Dummy onderwerp 1', null],
            $subjects,
        );
    }

    public function testGetOptionalStringReturnsNullForNonExistingColumn(): void
    {
        $values = [];
        foreach ($this->excelReader as $row) {
            $values[] = $this->excelReader->getOptionalString($row->getRowIndex(), 'foobar');
        }

        $this->assertEquals(
            [null, null],
            $values,
        );
    }
}
