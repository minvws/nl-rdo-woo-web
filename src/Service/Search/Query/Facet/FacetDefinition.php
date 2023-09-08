<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Aggregation\AggregationStrategyInterface;
use App\Service\Search\Query\Filter\FilterInterface;

class FacetDefinition
{
    public function __construct(
        private readonly FacetKey $key,
        private readonly string $path,
        private readonly string $queryParam,
        private readonly ?FilterInterface $filter = null,
        private readonly ?AggregationStrategyInterface $aggregationStrategy = null,
    ) {
    }

    public function getFacetKey(): string
    {
        return $this->key->value;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFilter(): ?FilterInterface
    {
        return $this->filter;
    }

    public function getQueryParam(): string
    {
        return $this->queryParam;
    }

    public function getAggregationStrategy(): ?AggregationStrategyInterface
    {
        return $this->aggregationStrategy;
    }
}
