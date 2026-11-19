<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;

abstract class RowImportException extends ImportException
{
    public function __construct(public readonly int $rowIndex, string $message)
    {
        parent::__construct(sprintf('%s in row #%d', $message, $rowIndex));
    }
}
