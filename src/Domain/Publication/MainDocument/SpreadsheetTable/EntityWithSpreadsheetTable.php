<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\SpreadsheetTable;

interface EntityWithSpreadsheetTable
{
    public function getSpreadsheetTable(): ?SpreadsheetTable;

    public function setSpreadsheetTable(?SpreadsheetTable $spreadsheetTable): void;

    public function isSpreadsheetTableTruncated(): bool;

    public function setSpreadsheetTableTruncated(bool $spreadsheetTableTruncated): void;
}
