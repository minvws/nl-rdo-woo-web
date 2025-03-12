<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Query\Facet\Facet;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Aggregation;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Constants\SortDirections;

readonly class TypeAggregationStrategy implements AggregationStrategyInterface
{
    public function getAggregation(Facet $facet, SearchParameters $searchParameters, int $maxCount): AbstractAggregation
    {
        return Aggregation::termsWithMinDocCount(
            name: ElasticField::TOPLEVEL_TYPE->value,
            fieldOrSource: ElasticField::TOPLEVEL_TYPE->value,
            minDocCount: 1,
            aggregations: [
                Aggregation::termsWithMinDocCount(
                    name: ElasticField::SUBLEVEL_TYPE->value,
                    fieldOrSource: ElasticField::SUBLEVEL_TYPE->value,
                    minDocCount: 1,
                )
                    ->setSize($maxCount)
                    ->setOrder('_count', SortDirections::DESC),
                Aggregation::missing(
                    name: 'publication',
                    field: ElasticField::SUBLEVEL_TYPE->value,
                ),
            ]
        );
    }

    public function excludeOwnFilters(): bool
    {
        return true;
    }
}
