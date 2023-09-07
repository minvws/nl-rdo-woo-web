<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Dsl\TermsAggregationWithMinDocCount;
use App\Service\Search\Query\Facet\FacetDefinition;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Constants\SortDirections;

class TermsAggregationStrategy implements AggregationStrategyInterface
{
    public function __construct(
        // Set to false for AND behaviour in facet counts.
        private readonly bool $excludeOwnFilters = true,
    ) {
    }

    public function getAggregation(FacetDefinition $facet, Config $config, int $maxCount): AbstractAggregation
    {
        return new TermsAggregationWithMinDocCount(
            name: $facet->getFacetKey(),
            fieldOrSource: $facet->getPath(),
            minDocCount: 1,
            orderField: '_count',
            orderValue: SortDirections::DESC,
            size: $maxCount,
        );
    }

    public function excludeOwnFilters(): bool
    {
        return $this->excludeOwnFilters;
    }
}
