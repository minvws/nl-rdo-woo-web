<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\FileReader;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Shared\Exception\FileReaderException;
use Shared\Service\FileReader\CsvReader;
use Shared\Service\FileReader\HeaderMap;
use Shared\Service\Inventory\MetadataField;
use Shared\Tests\Unit\UnitTestCase;

use function end;
use function iterator_to_array;

use const DIRECTORY_SEPARATOR;

class CsvReaderTest extends UnitTestCase
{
    private CsvReader $csvReader;
    private HeaderMap&MockInterface $headerMap;

    protected function setUp(): void
    {
        $this->headerMap = Mockery::mock(HeaderMap::class);

        $this->csvReader = new CsvReader(
            __DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.csv',
            $this->headerMap,
        );
    }

    public function testIteratorSkipsFirstRowAndEmptyRows(): void
    {
        $this->headerMap->expects('has')->times(2)->with('id')->andReturnTrue();
        $this->headerMap->expects('getCellCoordinate')->times(2)->with('id')->andReturn(1);

        $ids = [];
        foreach ($this->csvReader as $rowIndex => $data) {
            $ids[] = $this->csvReader->getInt($rowIndex, MetadataField::ID->value);
        }

        self::assertEquals(
            [5033, 5034],
            $ids,
        );
    }

    public function testGetOptionalStringReturnsNullForNonExistingColumn(): void
    {
        $this->headerMap->expects('has')->times(2)->with('foobar')->andReturnFalse();

        $values = [];
        foreach ($this->csvReader as $rowIndex => $data) {
            $values[] = $this->csvReader->getOptionalString($rowIndex, 'foobar');
        }

        self::assertEquals(
            [null, null],
            $values,
        );
    }

    public function testGetOptionalIntReturnsValueForExistingColumnWhenFilled(): void
    {
        $this->headerMap->expects('has')->times(2)->with('family')->andReturnTrue();
        $this->headerMap->expects('getCellCoordinate')->times(2)->with('family')->andReturn(0);

        $familyIds = [];
        foreach ($this->csvReader as $rowIndex => $data) {
            $familyIds[] = $this->csvReader->getOptionalInt($rowIndex, 'family');
        }

        self::assertEquals(
            [5033, null],
            $familyIds,
        );
    }

    public function testGetOptionalIntReturnsNullForNonExistingColumn(): void
    {
        $this->headerMap->expects('has')->times(2)->with('foobar')->andReturnFalse();

        $values = [];
        foreach ($this->csvReader as $rowIndex => $data) {
            $values[] = $this->csvReader->getOptionalInt($rowIndex, 'foobar');
        }

        self::assertEquals(
            [null, null],
            $values,
        );
    }

    public function testGetOptionalIntReturnsNullForEmptyColumn(): void
    {
        $this->headerMap->expects('has')->with('family')->andReturnTrue();
        $this->headerMap->expects('getCellCoordinate')->with('family')->andReturn('');

        self::assertNull(
            $this->csvReader->getOptionalInt(1, 'family'),
        );
    }

    public function testGetDateTimeReturnsValueForExistingColumnWhenFilledAndThrowsExceptionForMissingValue(): void
    {
        $this->headerMap->expects('has')->times(2)->with('date')->andReturnTrue();
        $this->headerMap->expects('getCellCoordinate')->times(2)->with('date')->andReturn(5);

        $rows = iterator_to_array($this->csvReader);

        // First row has a valid date
        self::assertEquals(
            new DateTimeImmutable('2022-10-09 13:34'),
            $this->csvReader->getDateTime(1, 'date'),
        );

        // Last row has an empty value in date column
        end($rows);
        $this->expectException(FileReaderException::class);
        $this->csvReader->getDateTime(2, 'date');
    }

    public function testGetDateTimeThrowsExceptionForInvalidDate(): void
    {
        $this->headerMap->expects('has')->with('subject')->andReturnTrue();
        $this->headerMap->expects('getCellCoordinate')->with('subject')->andReturn(8);

        $this->expectException(FileReaderException::class);

        foreach ($this->csvReader as $rowIndex => $data) {
            $this->csvReader->getDateTime($rowIndex, 'subject'); // This field cannot be parsed as a date
        }
    }

    public function testGetOptionalDateTimeReturnsValueForExistingColumnWhenFilled(): void
    {
        $this->headerMap->expects('has')->times(2)->with('date')->andReturnTrue();
        $this->headerMap->expects('getCellCoordinate')->times(2)->with('date')->andReturn(5);

        $dates = [];
        foreach ($this->csvReader as $rowIndex => $data) {
            $dates[] = $this->csvReader->getOptionalDateTime($rowIndex, 'date');
        }

        self::assertEquals(
            [new DateTimeImmutable('2022-10-09 13:34'), null],
            $dates,
        );
    }

    public function testGetOptionalDateTimeReturnsNullForNonExistingColumn(): void
    {
        $this->headerMap->expects('has')->times(2)->with('non-existent-column')->andReturnFalse();

        $dates = [];
        foreach ($this->csvReader as $rowIndex => $data) {
            $dates[] = $this->csvReader->getOptionalDateTime($rowIndex, 'non-existent-column');
        }

        self::assertEquals(
            [null, null],
            $dates,
        );
    }

    public function testGetOptionalDateTimeThrowsExceptionOnInvalidFormat(): void
    {
        $this->headerMap->expects('has')
            ->with('date')
            ->andReturnTrue();
        $this->headerMap->expects('getCellCoordinate')
            ->with('date')
            ->andReturn(4);

        self::expectException(FileReaderException::class);
        $this->csvReader->getOptionalDateTime(1, 'date');
    }

    public function testGetCount(): void
    {
        self::assertEquals(2, $this->csvReader->getCount());
    }
}
