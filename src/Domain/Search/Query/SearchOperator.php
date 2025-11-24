<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query;

enum SearchOperator: string
{
    case AND = 'and';
    case OR = 'or';
    case PHRASE = 'phrase';

    public const DEFAULT = self::AND;
}
