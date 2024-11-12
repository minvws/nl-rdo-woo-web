<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Sort;

class SortException extends \RuntimeException
{
    public static function forActiveSortNotFound(): self
    {
        return new self('No active sort found');
    }
}
