<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Service\Search\Model\Config;
use App\Service\Search\Query\Aggregation;
use App\Service\Search\Query\Facet\Facet;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Aggregation\FilterAggregation;
use Erichard\ElasticQueryBuilder\Constants\SortDirections;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

readonly class TypeAggregationStrategy implements AggregationStrategyInterface
{
    public function getAggregation(Facet $facet, Config $config, int $maxCount): AbstractAggregation
    {
        $aggregation = Aggregation::termsWithMinDocCount(
            name: $facet->getFacetKey()->value,
            fieldOrSource: $facet->getPath(),
            minDocCount: 1,
        )
            ->setSize($maxCount)
            ->setOrder('_count', SortDirections::DESC);

        return new FilterAggregation(
            $facet->getFacetKey()->value . '_name',
            new BoolQuery(
                filter: [
                    Query::Terms(
                        field: 'type',
                        values: array_map(
                            static fn (ElasticDocumentType $type) => $type->value,
                            ElasticDocumentType::getMainTypes(),
                        ),
                    ),
                ],
            ),
            [$aggregation]
        );
    }

    public function excludeOwnFilters(): bool
    {
        return true;
    }
}
