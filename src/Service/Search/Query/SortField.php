<?php

declare(strict_types=1);

namespace App\Service\Search\Query;

enum SortField: string
{
    case SCORE = '_score';
    case DECISION_DATE = 'decision_date';
    case PUBLICATION_DATE = 'publication_date';

    public static function fromValue(string $input): self
    {
        return self::tryFrom($input) ?? self::SCORE;
    }
}
