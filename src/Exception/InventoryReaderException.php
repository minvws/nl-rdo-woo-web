<?php

declare(strict_types=1);

namespace App\Exception;

class InventoryReaderException extends FileReaderException
{
    public static function forMissingDocumentIdInRow(int $rowIndex): self
    {
        return new self(
            "Missing document ID in inventory row #$rowIndex",
            'publication.dossier.error.missing_document_number',
            [
                '{rownumber}' => strval($rowIndex),
            ]
        );
    }

    public static function forInvalidDocumentId(int $rowIndex): self
    {
        return new self(
            "Invalid document ID in inventory row #$rowIndex",
            'publication.dossier.error.invalid_document_number',
            [
                '{rownumber}' => strval($rowIndex),
            ]
        );
    }

    public static function forInvalidDocumentIdLength(int $rowIndex, int $min, int $max): self
    {
        return new self(
            "Invalid document ID length in inventory row #$rowIndex",
            'publication.dossier.error.invalid_document_id_length',
            [
                '{rownumber}' => strval($rowIndex),
                '{min}' => strval($min),
                '{max}' => strval($max),
            ]
        );
    }

    public static function forInvalidMatterInRow(int $rowIndex, int $mix, int $max): self
    {
        return new self(
            "Invalid matter in inventory row #$rowIndex",
            'publication.dossier.error.invalid_matter',
            [
                '{rownumber}' => strval($rowIndex),
                '{min}' => strval($mix),
                '{max}' => strval($max),
            ]
        );
    }

    public static function forRemarkTooLong(int $rowIndex, int $max): self
    {
        return new self(
            "Remark in row #$rowIndex is too long",
            'publication.dossier.error.remark_too_long',
            [
                '{rownumber}' => strval($rowIndex),
                '{max}' => strval($max),
            ]
        );
    }

    public static function forInvalidFamilyId(int $rowIndex): self
    {
        return new self(
            "FamilyId in row #$rowIndex is invalid",
            'publication.dossier.error.invalid_family_id',
            [
                '{rownumber}' => strval($rowIndex),
            ]
        );
    }

    public static function forInvalidThreadId(int $rowIndex): self
    {
        return new self(
            "ThreadId in row #$rowIndex is invalid",
            'publication.dossier.error.invalid_thread_id',
            [
                '{rownumber}' => strval($rowIndex),
            ]
        );
    }

    public static function forLinkTooLong(string $link, int $rowIdx): self
    {
        return new self(
            "Link '$link' is too long in inventory row #$rowIdx",
            'publication.dossier.error.link_too_long',
            [
                '{rownumber}' => strval($rowIdx),
            ]
        );
    }

    public static function forFileTooLong(string $filename, int $rowIdx): self
    {
        return new self(
            "Filename '$filename' is too long in inventory row #$rowIdx",
            'publication.dossier.error.file_name_too_long',
            [
                '{rownumber}' => strval($rowIdx),
            ]
        );
    }

    public static function forCaseNumbersInvalid(string $caseNumbers, int $rowIdx): self
    {
        return new self(
            "Casenumbers '$caseNumbers' are not valid in inventory row #$rowIdx",
            'publication.dossier.error.casenumbers_invalid',
            [
                '{rownumber}' => strval($rowIdx),
            ]
        );
    }
}
