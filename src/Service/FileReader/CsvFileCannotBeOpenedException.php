<?php

declare(strict_types=1);

namespace Shared\Service\FileReader;

use RuntimeException;

use function sprintf;

final class CsvFileCannotBeOpenedException extends RuntimeException
{
    public function __construct(public readonly string $filepath)
    {
        parent::__construct(sprintf('Failed to open file: %s', $filepath));
    }
}
