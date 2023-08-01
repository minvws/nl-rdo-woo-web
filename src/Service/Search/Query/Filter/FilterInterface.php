<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

interface FilterInterface
{
    /**
     * @param mixed[] $values
     *
     * @return ?array<string, mixed>
     */
    public function getQuery(array $values): ?array;
}
