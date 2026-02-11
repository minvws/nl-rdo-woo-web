<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Sort;

use RuntimeException;

class SortException extends RuntimeException
{
    public static function forActiveSortNotFound(): self
    {
        return new self('No active sort found');
    }
}
