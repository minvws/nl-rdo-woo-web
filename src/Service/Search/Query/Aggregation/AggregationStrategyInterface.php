<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetDefinition;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;

interface AggregationStrategyInterface
{
    public function excludeOwnFilters(): bool;

    public function getAggregation(FacetDefinition $facet, Config $config, int $maxCount): AbstractAggregation;
}
