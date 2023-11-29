<?php

declare(strict_types=1);

namespace App\Service\Search\Query;

enum SortOrder: string
{
    case ASC = 'asc';
    case DESC = 'desc';

    public static function fromValue(string $input): self
    {
        return self::tryFrom($input) ?? self::DESC;
    }
}
