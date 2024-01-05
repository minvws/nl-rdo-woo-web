<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Excel;

use App\Exception\FileReaderException;
use App\Service\FileReader\ExcelReader;
use App\Service\FileReader\HeaderMap;
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
        $headerMap->expects('has')->with('family')->zeroOrMoreTimes()->andReturnTrue();
        $headerMap->expects('getCellCoordinate')->with('family')->zeroOrMoreTimes()->andReturn('A');
        $headerMap->expects('has')->with('date')->zeroOrMoreTimes()->andReturnTrue();
        $headerMap->expects('getCellCoordinate')->with('date')->zeroOrMoreTimes()->andReturn('F');

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

    public function testGetOptionalIntReturnsValueForExistingColumnWhenFilled(): void
    {
        $familyIds = [];
        foreach ($this->excelReader as $row) {
            $familyIds[] = $this->excelReader->getOptionalInt($row->getRowIndex(), 'family');
        }

        $this->assertEquals(
            [5033, null],
            $familyIds,
        );
    }

    public function testGetOptionalIntReturnsNullForNonExistingColumn(): void
    {
        $values = [];
        foreach ($this->excelReader as $row) {
            $values[] = $this->excelReader->getOptionalInt($row->getRowIndex(), 'foobar');
        }

        $this->assertEquals(
            [null, null],
            $values,
        );
    }

    public function testGetDateTimeReturnsValueForExistingColumnWhenFilled(): void
    {
        $dates = [];
        foreach ($this->excelReader as $row) {
            $dates[] = $this->excelReader->getDateTime($row->getRowIndex(), 'date');
        }

        $this->assertEquals(
            [new \DateTimeImmutable('2022-10-09 13:34'), new \DateTimeImmutable('2023-10-09 13:34')],
            $dates,
        );
    }

    public function testGetDateTimeThrowsExceptionForInvalidDate(): void
    {
        $this->expectException(FileReaderException::class);

        foreach ($this->excelReader as $row) {
            $this->excelReader->getDateTime($row->getRowIndex(), 'subject'); // This field cannot be parsed as a date
        }
    }
}
