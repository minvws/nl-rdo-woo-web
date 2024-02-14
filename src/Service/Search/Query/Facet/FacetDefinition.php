<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

use App\Service\Search\Model\Config;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Aggregation\AggregationStrategyInterface;
use App\Service\Search\Query\Filter\FilterInterface;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

final readonly class FacetDefinition
{
    public function __construct(
        private FacetKey $key,
        private ?FilterInterface $filter = null,
        private ?AggregationStrategyInterface $aggregationStrategy = null,
    ) {
    }

    public function getFacetKey(): FacetKey
    {
        return $this->key;
    }

    public function getFilter(): ?FilterInterface
    {
        return $this->filter;
    }

    public function getAggregationStrategy(): ?AggregationStrategyInterface
    {
        return $this->aggregationStrategy;
    }

    public function getPath(): string
    {
        return $this->key->getPath();
    }

    public function getDataClass(): string
    {
        return $this->key->getInputClass();
    }

    public function getParamName(): string
    {
        return $this->key->getParamName();
    }

    public function optionallyAddQueryToFilter(Facet $facet, BoolQuery $query, Config $config): void
    {
        $this->filter?->addToQuery($facet, $query, $config);
    }

    public function shouldExcludeOwnFilter(): bool
    {
        return $this->aggregationStrategy?->excludeOwnFilters() === true;
    }

    public function getOptionalAggregation(Facet $facet, Config $config, int $maxCount): ?AbstractAggregation
    {
        return $this->aggregationStrategy?->getAggregation($facet, $config, $maxCount);
    }
}
