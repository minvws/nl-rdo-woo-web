<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\FileReader;

use App\Exception\FileReaderException;
use App\Service\FileReader\ExcelReader;
use App\Service\FileReader\HeaderMap;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelReaderTest extends MockeryTestCase
{
    private ExcelReader $excelReader;

    public function setUp(): void
    {
        $headerMap = \Mockery::mock(HeaderMap::class);
        $headerMap->shouldReceive('has')->with('id')->andReturnTrue();
        $headerMap->shouldReceive('getCellCoordinate')->with('id')->andReturn('B');
        $headerMap->shouldReceive('has')->with('subject')->andReturnTrue();
        $headerMap->shouldReceive('getCellCoordinate')->with('subject')->andReturn('I');
        $headerMap->shouldReceive('has')->with('foobar')->andReturnFalse();
        $headerMap->shouldReceive('has')->with('family')->andReturnTrue();
        $headerMap->shouldReceive('getCellCoordinate')->with('family')->andReturn('A');
        $headerMap->shouldReceive('has')->with('date')->andReturnTrue();
        $headerMap->shouldReceive('getCellCoordinate')->with('date')->andReturn('F');
        $headerMap->shouldReceive('has')->with('non-existent-column')->andReturnFalse();

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

        self::assertEquals(
            [5033, 5034],
            $ids,
        );
    }

    public function testGetOptionalStringReturnsNullForNonExistingColumn(): void
    {
        $values = [];
        foreach ($this->excelReader as $row) {
            $values[] = $this->excelReader->getOptionalString($row->getRowIndex(), 'foobar');
        }

        self::assertEquals(
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

        self::assertEquals(
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

        self::assertEquals(
            [null, null],
            $values,
        );
    }

    public function testGetDateTimeReturnsValueForExistingColumnWhenFilledAndThrowsExceptionForMissingValue(): void
    {
        $rows = iterator_to_array($this->excelReader);

        // First row has a valid date
        self::assertEquals(
            new \DateTimeImmutable('2022-10-09 13:34'),
            $this->excelReader->getDateTime(reset($rows)->getRowIndex(), 'date'),
        );

        // Last row has an empty value in date column
        $this->expectException(FileReaderException::class);
        $this->excelReader->getDateTime(end($rows)->getRowIndex(), 'date');
    }

    public function testGetDateTimeThrowsExceptionForInvalidDate(): void
    {
        $this->expectException(FileReaderException::class);

        foreach ($this->excelReader as $row) {
            $this->excelReader->getDateTime($row->getRowIndex(), 'subject'); // This field cannot be parsed as a date
        }
    }

    public function testGetOptionalDateTimeReturnsValueForExistingColumnWhenFilled(): void
    {
        $dates = [];
        foreach ($this->excelReader as $row) {
            $dates[] = $this->excelReader->getOptionalDateTime($row->getRowIndex(), 'date');
        }

        self::assertEquals(
            [new \DateTimeImmutable('2022-10-09 13:34'), null],
            $dates,
        );
    }

    public function testGetOptionalDateTimeReturnsNullForNonExistingColumn(): void
    {
        $dates = [];
        foreach ($this->excelReader as $row) {
            $dates[] = $this->excelReader->getOptionalDateTime($row->getRowIndex(), 'non-existent-column');
        }

        self::assertEquals(
            [null, null],
            $dates,
        );
    }
}
