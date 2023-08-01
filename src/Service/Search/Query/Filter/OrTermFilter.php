<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

/**
 * Meaning that at least one value must match.
 */
class OrTermFilter implements FilterInterface
{
    public function __construct(protected string $field)
    {
    }

    /**
     * @param mixed[] $values
     *
     * @return array<string, mixed>
     */
    public function getQuery(array $values): array
    {
        return [
            'terms' => [
                $this->field => $values,
            ],
        ];
    }
}
