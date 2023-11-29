<?php

declare(strict_types=1);

namespace App\Service\Excel;

/**
 * Describes the column mapping for an Excel file.
 * Used as input for validating and parsing an Excel file by the ExcelReader.
 *
 * Properties:
 * - name: a single name/identifier as used internally by the code
 * - isRequired: should the processing fail if the column is missing? If 'false' NULL values are returned when the column is used.
 * - columnNames: all names/aliases that can be used for this column in the Excel files.
 */
class ColumnMapping
{
    public function __construct(
        private readonly string $name,
        private readonly bool $required,
        /** @var string[] */
        private readonly array $columnNames,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return string[]
     */
    public function getColumnNames(): array
    {
        return $this->columnNames;
    }
}
