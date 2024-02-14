<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Aggregation;
use App\Service\Search\Query\Facet\Facet;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Constants\SortDirections;

readonly class TermsAggregationStrategy implements AggregationStrategyInterface
{
    /**
     * @param bool $excludeOwnFilters Set to false for AND behaviour in facet counts
     */
    public function __construct(
        private bool $excludeOwnFilters = true,
        private bool $orderByKey = false,
    ) {
    }

    public function getAggregation(Facet $facet, Config $config, int $maxCount): AbstractAggregation
    {
        $aggregation = Aggregation::termsWithMinDocCount(
            name: $facet->getFacetKey()->value,
            fieldOrSource: $facet->getPath(),
            minDocCount: 1,
        )->setSize($maxCount);

        if ($this->orderByKey) {
            $aggregation->setOrder('_key');
        } else {
            $aggregation->setOrder('_count', SortDirections::DESC);
        }

        return $aggregation;
    }

    public function excludeOwnFilters(): bool
    {
        return $this->excludeOwnFilters;
    }
}
