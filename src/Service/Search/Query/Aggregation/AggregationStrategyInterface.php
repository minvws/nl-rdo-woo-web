<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

use App\Domain\Search\Query\Facet\Facet;
use App\Domain\Search\Query\SearchParameters;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;

interface AggregationStrategyInterface
{
    public function excludeOwnFilters(): bool;

    public function getAggregation(Facet $facet, SearchParameters $searchParameters, int $maxCount): ?AbstractAggregation;
}
