<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Aggregation;

use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Shared\Domain\Search\Query\Facet\Facet;
use Shared\Domain\Search\Query\SearchParameters;

interface AggregationStrategyInterface
{
    public function excludeOwnFilters(): bool;

    public function getAggregation(Facet $facet, SearchParameters $searchParameters, int $maxCount): ?AbstractAggregation;
}
