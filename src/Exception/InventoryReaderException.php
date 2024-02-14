<?php

declare(strict_types=1);

namespace App\Exception;

class InventoryReaderException extends FileReaderException
{
    public static function forMissingDocumentIdInRow(int $rowIndex): self
    {
        return new self(
            "Missing document ID in inventory row #$rowIndex",
            'Missing document number in row {rownumber}',
            [
                '{rownumber}' => strval($rowIndex),
            ]
        );
    }

    public static function forInvalidDocumentId(int $rowIndex): self
    {
        return new self(
            "Invalid document ID in inventory row #$rowIndex",
            'Invalid document number in row {rownumber}',
            [
                '{rownumber}' => strval($rowIndex),
            ]
        );
    }

    public static function forMissingMatterInRow(int $rowIndex): self
    {
        return new self(
            "Missing matter in inventory row #$rowIndex",
            'Missing matter in row {rownumber}',
            [
                '{rownumber}' => strval($rowIndex),
            ]
        );
    }

    public static function forLinkTooLong(string $link, int $rowIdx): self
    {
        return new self(
            "Link '$link' is too long in inventory row #$rowIdx",
            'Link too long in row {rownumber}',
            [
                '{rownumber}' => strval($rowIdx),
            ]
        );
    }

    public static function forFileTooLong(string $filename, int $rowIdx): self
    {
        return new self(
            "Filename '$filename' is too long in inventory row #$rowIdx",
            'Filename too long in row {rownumber}',
            [
                '{rownumber}' => strval($rowIdx),
            ]
        );
    }
}
