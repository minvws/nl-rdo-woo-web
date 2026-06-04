<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\FileReader;

use DateTimeImmutable;
use Mockery;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use Shared\Exception\FileReaderException;
use Shared\Service\FileReader\ExcelReader;
use Shared\Service\FileReader\HeaderMap;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

use function end;
use function iterator_to_array;
use function reset;

use const DIRECTORY_SEPARATOR;

class ExcelReaderTest extends UnitTestCase
{
    public function testIteratorSkipsFirstRowAndEmptyRows(): void
    {
        $headerMap = Mockery::mock(HeaderMap::class);
        $headerMap->expects('getCellCoordinate')->times(2)->with('id')->andReturn('B');

        $worksheet = IOFactory::load(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.xlsx');
        $excelReader = new ExcelReader(
            $worksheet->getSheet(0),
            $headerMap,
        );

        $ids = [];
        foreach ($excelReader as $row) {
            $ids[] = $excelReader->getInt($row->getRowIndex(), 'id');
        }

        self::assertEquals(
            [5033, 5034],
            $ids,
        );
    }

    public function testGetOptionalStringReturnsNullForNonExistingColumn(): void
    {
        $headerMap = Mockery::mock(HeaderMap::class);
        $headerMap->expects('has')->times(2)->with('foobar')->andReturnFalse();

        $worksheet = IOFactory::load(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.xlsx');
        $excelReader = new ExcelReader(
            $worksheet->getSheet(0),
            $headerMap,
        );

        $values = [];
        foreach ($excelReader as $row) {
            $values[] = $excelReader->getOptionalString($row->getRowIndex(), 'foobar');
        }

        self::assertEquals(
            [null, null],
            $values,
        );
    }

    public function testGetOptionalIntReturnsValueForExistingColumnWhenFilled(): void
    {
        $headerMap = Mockery::mock(HeaderMap::class);
        $headerMap->expects('has')->times(2)->with('family')->andReturnTrue();
        $headerMap->expects('getCellCoordinate')->times(2)->with('family')->andReturn('A');

        $worksheet = IOFactory::load(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.xlsx');
        $excelReader = new ExcelReader(
            $worksheet->getSheet(0),
            $headerMap,
        );

        $familyIds = [];
        foreach ($excelReader as $row) {
            $familyIds[] = $excelReader->getOptionalInt($row->getRowIndex(), 'family');
        }

        self::assertEquals(
            [5033, null],
            $familyIds,
        );
    }

    public function testGetOptionalIntReturnsNullForNonExistingColumn(): void
    {
        $headerMap = Mockery::mock(HeaderMap::class);
        $headerMap->expects('has')->times(2)->with('foobar')->andReturnFalse();

        $worksheet = IOFactory::load(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.xlsx');
        $excelReader = new ExcelReader(
            $worksheet->getSheet(0),
            $headerMap,
        );

        $values = [];
        foreach ($excelReader as $row) {
            $values[] = $excelReader->getOptionalInt($row->getRowIndex(), 'foobar');
        }

        self::assertEquals(
            [null, null],
            $values,
        );
    }

    public function testGetDateTimeReturnsValueForExistingColumnWhenFilledAndThrowsExceptionForMissingValue(): void
    {
        $headerMap = Mockery::mock(HeaderMap::class);
        $headerMap->expects('getCellCoordinate')->times(2)->with('date')->andReturn('F');

        $worksheet = IOFactory::load(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.xlsx');
        $excelReader = new ExcelReader(
            $worksheet->getSheet(0),
            $headerMap,
        );

        $rows = iterator_to_array($excelReader, false);

        // First row has a valid date
        $first = reset($rows);
        Assert::isInstanceOf($first, Row::class);
        self::assertEquals(
            new DateTimeImmutable('2022-10-09 13:34'),
            $excelReader->getDateTime($first->getRowIndex(), 'date'),
        );

        // Last row has an empty value in date column
        $last = end($rows);
        Assert::isInstanceOf($last, Row::class);
        $this->expectException(FileReaderException::class);
        $excelReader->getDateTime($last->getRowIndex(), 'date');
    }

    public function testGetDateTimeThrowsExceptionForInvalidDate(): void
    {
        $headerMap = Mockery::mock(HeaderMap::class);
        $headerMap->expects('getCellCoordinate')->with('subject')->andReturn('I');

        $worksheet = IOFactory::load(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.xlsx');
        $excelReader = new ExcelReader(
            $worksheet->getSheet(0),
            $headerMap,
        );

        $this->expectException(FileReaderException::class);

        foreach ($excelReader as $row) {
            $excelReader->getDateTime($row->getRowIndex(), 'subject'); // This field cannot be parsed as a date
        }
    }

    public function testGetOptionalDateTimeReturnsValueForExistingColumnWhenFilled(): void
    {
        $headerMap = Mockery::mock(HeaderMap::class);
        $headerMap->expects('has')->times(2)->with('date')->andReturnTrue();
        $headerMap->expects('getCellCoordinate')->times(2)->with('date')->andReturn('F');

        $worksheet = IOFactory::load(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.xlsx');
        $excelReader = new ExcelReader(
            $worksheet->getSheet(0),
            $headerMap,
        );

        $dates = [];
        foreach ($excelReader as $row) {
            $dates[] = $excelReader->getOptionalDateTime($row->getRowIndex(), 'date');
        }

        self::assertEquals(
            [new DateTimeImmutable('2022-10-09 13:34'), null],
            $dates,
        );
    }

    public function testGetOptionalDateTimeReturnsNullForNonExistingColumn(): void
    {
        $headerMap = Mockery::mock(HeaderMap::class);
        $headerMap->expects('has')->times(2)->with('non-existent-column')->andReturnFalse();

        $worksheet = IOFactory::load(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.xlsx');
        $excelReader = new ExcelReader(
            $worksheet->getSheet(0),
            $headerMap,
        );

        $dates = [];
        foreach ($excelReader as $row) {
            $dates[] = $excelReader->getOptionalDateTime($row->getRowIndex(), 'non-existent-column');
        }

        self::assertEquals(
            [null, null],
            $dates,
        );
    }

    public function testGetOptionalDateTimeThrowsExceptionOnInvalidFormat(): void
    {
        $headerMap = Mockery::mock(HeaderMap::class);
        $headerMap->expects('has')->with('date')->andReturnTrue();
        $headerMap->expects('getCellCoordinate')->with('date')->andReturn('B');

        $worksheet = IOFactory::load(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.xlsx');
        $excelReader = new ExcelReader(
            $worksheet->getSheet(0),
            $headerMap,
        );

        self::expectException(FileReaderException::class);
        $excelReader->getOptionalDateTime(1, 'date');
    }

    public function testGetCount(): void
    {
        $headerMap = Mockery::mock(HeaderMap::class);

        $worksheet = IOFactory::load(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.xlsx');
        $excelReader = new ExcelReader($worksheet->getSheet(0), $headerMap);

        self::assertEquals(11, $excelReader->getCount());
    }
}
