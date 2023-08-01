<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

/**
 * Meaning that all values must match.
 */
class AndTermFilter implements FilterInterface
{
    public function __construct(protected string $field)
    {
    }

    public function getQuery(array $values): ?array
    {
        $filters = [];
        foreach ($values as $value) {
            $filters[] = ['term' => [$this->field => $value]];
        }

        return [
            'bool' => [
                'filter' => $filters,
            ],
        ];
    }
}
