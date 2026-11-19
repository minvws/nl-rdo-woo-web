<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\SpreadsheetTable;

final readonly class SpreadsheetTableResult
{
    public function __construct(
        public SpreadsheetTable $table,
        public bool $truncated,
    ) {
    }
}
