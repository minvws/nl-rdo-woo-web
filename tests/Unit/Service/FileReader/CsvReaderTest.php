<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\FileReader;

use App\Exception\FileReaderException;
use App\Service\FileReader\CsvReader;
use App\Service\FileReader\HeaderMap;
use App\Service\Inventory\MetadataField;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class CsvReaderTest extends MockeryTestCase
{
    private CsvReader $csvReader;
    private HeaderMap&MockInterface $headerMap;

    public function setUp(): void
    {
        $this->headerMap = \Mockery::mock(HeaderMap::class);

        $this->csvReader = new CsvReader(
            __DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.csv',
            $this->headerMap,
        );
    }

    public function testIteratorSkipsFirstRowAndEmptyRows(): void
    {
        $this->headerMap->shouldReceive('has')->with('id')->andReturnTrue();
        $this->headerMap->shouldReceive('getCellCoordinate')->with('id')->andReturn(1);

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
        $this->headerMap->shouldReceive('has')->with('foobar')->andReturnFalse();

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
        $this->headerMap->shouldReceive('has')->with('family')->andReturnTrue();
        $this->headerMap->shouldReceive('getCellCoordinate')->with('family')->andReturn(0);

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
        $this->headerMap->shouldReceive('has')->with('foobar')->andReturnFalse();

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
        $this->headerMap->shouldReceive('has')->with('family')->andReturnTrue();
        $this->headerMap->shouldReceive('getCellCoordinate')->with('family')->andReturn('');

        self::assertNull(
            $this->csvReader->getOptionalInt(1, 'family'),
        );
    }

    public function testGetDateTimeReturnsValueForExistingColumnWhenFilledAndThrowsExceptionForMissingValue(): void
    {
        $this->headerMap->shouldReceive('has')->with('date')->andReturnTrue();
        $this->headerMap->shouldReceive('getCellCoordinate')->with('date')->andReturn(5);

        $rows = iterator_to_array($this->csvReader);

        // First row has a valid date
        self::assertEquals(
            new \DateTimeImmutable('2022-10-09 13:34'),
            $this->csvReader->getDateTime(key($rows), 'date'),
        );

        // Last row has an empty value in date column
        end($rows);
        $this->expectException(FileReaderException::class);
        $this->csvReader->getDateTime(key($rows), 'date');
    }

    public function testGetDateTimeThrowsExceptionForInvalidDate(): void
    {
        $this->headerMap->shouldReceive('has')->with('subject')->andReturnTrue();
        $this->headerMap->shouldReceive('getCellCoordinate')->with('subject')->andReturn(8);

        $this->expectException(FileReaderException::class);

        foreach ($this->csvReader as $rowIndex => $data) {
            $this->csvReader->getDateTime($rowIndex, 'subject'); // This field cannot be parsed as a date
        }
    }

    public function testGetOptionalDateTimeReturnsValueForExistingColumnWhenFilled(): void
    {
        $this->headerMap->shouldReceive('has')->with('date')->andReturnTrue();
        $this->headerMap->shouldReceive('getCellCoordinate')->with('date')->andReturn(5);

        $dates = [];
        foreach ($this->csvReader as $rowIndex => $data) {
            $dates[] = $this->csvReader->getOptionalDateTime($rowIndex, 'date');
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
        foreach ($this->csvReader as $rowIndex => $data) {
            $dates[] = $this->csvReader->getOptionalDateTime($rowIndex, 'non-existent-column');
        }

        self::assertEquals(
            [null, null],
            $dates,
        );
    }
}
