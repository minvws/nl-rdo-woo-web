<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\FileReader;

use App\Exception\FileReaderException;
use App\Service\FileReader\CsvReader;
use App\Service\FileReader\HeaderMap;
use App\Service\Inventory\MetadataField;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class CsvReaderTest extends MockeryTestCase
{
    private CsvReader $csvReader;

    public function setUp(): void
    {
        $headerMap = \Mockery::mock(HeaderMap::class);
        $headerMap->shouldReceive('has')->with('id')->andReturnTrue();
        $headerMap->shouldReceive('getCellCoordinate')->with('id')->andReturn(1);
        $headerMap->shouldReceive('has')->with('subject')->andReturnTrue();
        $headerMap->shouldReceive('getCellCoordinate')->with('subject')->andReturn(8);
        $headerMap->shouldReceive('has')->with('foobar')->andReturnFalse();
        $headerMap->shouldReceive('has')->with('family')->andReturnTrue();
        $headerMap->shouldReceive('getCellCoordinate')->with('family')->andReturn(0);
        $headerMap->shouldReceive('has')->with('date')->andReturnTrue();
        $headerMap->shouldReceive('getCellCoordinate')->with('date')->andReturn(5);
        $headerMap->shouldReceive('has')->with('non-existent-column')->andReturnFalse();

        $this->csvReader = new CsvReader(__DIR__ . DIRECTORY_SEPARATOR . 'inventory-with-empty-row.csv', $headerMap);
    }

    public function testIteratorSkipsFirstRowAndEmptyRows(): void
    {
        $ids = [];
        foreach ($this->csvReader as $rowIndex => $data) {
            $ids[] = $this->csvReader->getInt($rowIndex, MetadataField::ID->value);
        }

        self::assertEquals(
            [5033, 5034],
            $ids,
        );
    }

    public function testGetOptionalStringReturnsValueForExistingColumnWhenFilled(): void
    {
        $subjects = [];
        foreach ($this->csvReader as $rowIndex => $data) {
            $subjects[] = $this->csvReader->getOptionalString($rowIndex, 'subject');
        }

        self::assertEquals(
            ['Dummy onderwerp 1', null],
            $subjects,
        );
    }

    public function testGetOptionalStringReturnsNullForNonExistingColumn(): void
    {
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
        $values = [];
        foreach ($this->csvReader as $rowIndex => $data) {
            $values[] = $this->csvReader->getOptionalInt($rowIndex, 'foobar');
        }

        self::assertEquals(
            [null, null],
            $values,
        );
    }

    public function testGetDateTimeReturnsValueForExistingColumnWhenFilledAndThrowsExceptionForMissingValue(): void
    {
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
        $this->expectException(FileReaderException::class);

        foreach ($this->csvReader as $rowIndex => $data) {
            $this->csvReader->getDateTime($rowIndex, 'subject'); // This field cannot be parsed as a date
        }
    }

    public function testGetOptionalDateTimeReturnsValueForExistingColumnWhenFilled(): void
    {
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
