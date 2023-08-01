<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

interface AggregationStrategyInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getQuery(): array;
}
