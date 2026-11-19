<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTable;

readonly class SpreadsheetPreview
{
    public function __construct(
        public SpreadsheetTable $table,
        public bool $isTruncated,
    ) {
    }
}
