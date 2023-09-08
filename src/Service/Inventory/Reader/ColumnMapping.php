<?php

declare(strict_types=1);

namespace App\Service\Inventory\Reader;

use App\Service\Inventory\MetadataField;

class ColumnMapping
{
    public function __construct(
        private readonly MetadataField $field,
        private readonly bool $required,
        /** @var string[] */
        private readonly array $columnNames,
    ) {
    }

    public function getField(): MetadataField
    {
        return $this->field;
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

    public function matches(string $columnName): bool
    {
        foreach ($this->columnNames as $name) {
            // Check if it matches the header with some fuzziness
            if (levenshtein(strtolower($name), $columnName) < 2) {
                return true;
            }
        }

        return false;
    }
}
