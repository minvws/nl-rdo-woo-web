<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\SpreadsheetTable;

final readonly class SpreadsheetTable
{
    /**
     * @param list<string> $columns
     * @param list<list<string>> $rows
     */
    public function __construct(
        public array $columns,
        public array $rows,
    ) {
    }

    /**
     * @param array{columns: list<string>, rows: list<list<string>>} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['columns'], $data['rows']);
    }

    /**
     * @return array{columns: list<string>, rows: list<list<string>>}
     */
    public function toArray(): array
    {
        return [
            'columns' => $this->columns,
            'rows' => $this->rows,
        ];
    }
}
