<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\FileReader;

use App\Exception\FileReaderException;
use App\Service\FileReader\ExcelReader;
use App\Service\FileReader\HeaderMap;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelReaderTest extends MockeryTestCase
{
    private ExcelReader $excelReader;
    private HeaderMap&MockInterface $headerMap;

    public function setUp(): void
    {
        $this->headerMap = \Mockery::mock(HeaderMap::class);

        // The worksheet is unfortunately very hard to mock accurately
        $worksheet = IOFactory::load(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.xlsx');
        $this->excelReader = new ExcelReader(
            $worksheet->getSheet(0),
            $this->headerMap,
        );
    }

    public function testIteratorSkipsFirstRowAndEmptyRows(): void
    {
        $this->headerMap->shouldReceive('has')->with('id')->andReturnTrue();
        $this->headerMap->shouldReceive('getCellCoordinate')->with('id')->andReturn('B');

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
        $this->headerMap->shouldReceive('has')->with('foobar')->andReturnFalse();

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
        $this->headerMap->shouldReceive('has')->with('family')->andReturnTrue();
        $this->headerMap->shouldReceive('getCellCoordinate')->with('family')->andReturn('A');

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
        $this->headerMap->shouldReceive('has')->with('foobar')->andReturnFalse();

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
        $this->headerMap->shouldReceive('has')->with('date')->andReturnTrue();
        $this->headerMap->shouldReceive('getCellCoordinate')->with('date')->andReturn('F');

        $rows = iterator_to_array($this->excelReader, false);

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
        $this->headerMap->shouldReceive('has')->with('subject')->andReturnTrue();
        $this->headerMap->shouldReceive('getCellCoordinate')->with('subject')->andReturn('I');

        $this->expectException(FileReaderException::class);

        foreach ($this->excelReader as $row) {
            $this->excelReader->getDateTime($row->getRowIndex(), 'subject'); // This field cannot be parsed as a date
        }
    }

    public function testGetOptionalDateTimeReturnsValueForExistingColumnWhenFilled(): void
    {
        $this->headerMap->shouldReceive('has')->with('date')->andReturnTrue();
        $this->headerMap->shouldReceive('getCellCoordinate')->with('date')->andReturn('F');

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
        $this->headerMap->shouldReceive('has')->with('non-existent-column')->andReturnFalse();

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
