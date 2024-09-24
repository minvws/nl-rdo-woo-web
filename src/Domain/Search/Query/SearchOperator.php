<?php

declare(strict_types=1);

namespace App\Domain\Search\Query;

enum SearchOperator: string
{
    case AND = 'and';
    case OR = 'or';
    case PHRASE = 'phrase';

    public const DEFAULT = self::AND;
}
