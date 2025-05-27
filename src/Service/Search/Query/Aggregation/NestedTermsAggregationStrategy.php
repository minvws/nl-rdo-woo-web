<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

use App\Domain\Search\Query\Facet\Facet;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Dsl\Aggregation;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Constants\SortDirections;

class NestedTermsAggregationStrategy implements AggregationStrategyInterface
{
    public function __construct(
        private readonly string $path,
        private readonly bool $excludeOwnFilters = true,
    ) {
    }

    public function getAggregation(Facet $facet, SearchParameters $searchParameters, int $maxCount): AbstractAggregation
    {
        if ($searchParameters->searchType->isDossier()) {
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
