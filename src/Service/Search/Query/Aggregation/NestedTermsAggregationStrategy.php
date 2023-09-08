<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Dsl\TermsAggregationWithMinDocCount;
use App\Service\Search\Query\Facet\FacetDefinition;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Aggregation\NestedAggregation;
use Erichard\ElasticQueryBuilder\Constants\SortDirections;

class NestedTermsAggregationStrategy implements AggregationStrategyInterface
{
    public function __construct(
        private readonly string $path,
        private readonly bool $excludeOwnFilters = true,
    ) {
    }

    public function getAggregation(FacetDefinition $facet, Config $config, int $maxCount): AbstractAggregation
    {
        if ($config->searchType === Config::TYPE_DOSSIER) {
            return new TermsAggregationWithMinDocCount(
                name: $facet->getFacetKey(),
                fieldOrSource: $facet->getPath(),
                minDocCount: 1,
                orderField: '_count',
                orderValue: SortDirections::DESC,
                size: $maxCount,
            );
        }

        return new NestedAggregation(
            name: sprintf('%s-%s', $this->path, $facet->getFacetKey()),
            path: $this->path,
            aggregations: [
                new TermsAggregationWithMinDocCount(
                    name: $facet->getFacetKey(),
                    fieldOrSource: sprintf('%s.%s', $this->path, $facet->getPath()),
                    minDocCount: 1,
                    orderField: '_count',
                    orderValue: SortDirections::DESC,
                    size: $maxCount,
                ),
            ]
        );
    }

    public function excludeOwnFilters(): bool
    {
        return $this->excludeOwnFilters;
    }
}
