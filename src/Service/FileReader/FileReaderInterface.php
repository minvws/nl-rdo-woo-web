<?php

declare(strict_types=1);

namespace App\Service\FileReader;

/**
 * @template-extends \IteratorAggregate<mixed>
 *
 * It would make more sense from a CSV perspective to have the the FileReaderInterface to only use the getIterator. From that iterator we have a
 * "row" on which we can do getCell, getString etc. This way we do not have to store the whole CSV file directly into memory but we can use a
 * generator instead to read the file line by line.
 */
interface FileReaderInterface extends \IteratorAggregate
{
    // Retrieves a string from the given cell coordinate, will cause an error if missing
    public function getString(int $rowIndex, string $columnName): string;

    // Retrieves a string from the given cell coordinate, or null if the cell is empty.
    public function getOptionalString(int $rowIndex, string $columnName): ?string;

    // Retrieves an integer from the given cell coordinate.
    public function getInt(int $rowIndex, string $columnName): int;

    // Retrieves a datetime from the given cell coordinate, throws a FileReaderException when date is invalid.
    public function getDateTime(int $rowIndex, string $columnName): \DateTimeImmutable;

    // Retrieves the number of rows in the file.
    public function getCount(): int;

    // Retrieves an integer from the given cell coordinate, or null if the cell is empty.
    public function getOptionalInt(int $rowIndex, string $columnName): ?int;
}
