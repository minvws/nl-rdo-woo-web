<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Aggregation;
use App\Service\Search\Query\Facet\Facet;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Constants\SortDirections;

class NestedTermsAggregationStrategy implements AggregationStrategyInterface
{
    public function __construct(
        private readonly string $path,
        private readonly bool $excludeOwnFilters = true,
    ) {
    }

    public function getAggregation(Facet $facet, Config $config, int $maxCount): AbstractAggregation
    {
        if ($config->searchType === Config::TYPE_DOSSIER) {
            return Aggregation::termsWithMinDocCount(
                name: $facet->getFacetKey()->value,
                fieldOrSource: $facet->getPath(),
                minDocCount: 1,
            )
                ->setOrder('_count', SortDirections::DESC)
                ->setSize($maxCount);
        }

        return Aggregation::nested(
            name: sprintf('%s-%s', $this->path, $facet->getFacetKey()->value),
            path: $this->path,
        )->setAggregations([
            Aggregation::termsWithMinDocCount(
                name: $facet->getFacetKey()->value,
                fieldOrSource: sprintf('%s.%s', $this->path, $facet->getPath()),
                minDocCount: 1,
            )
                ->setOrder('_count', SortDirections::DESC)
                ->setSize($maxCount),
        ]);
    }

    public function excludeOwnFilters(): bool
    {
        return $this->excludeOwnFilters;
    }
}
