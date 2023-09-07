<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetDefinition;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

interface FilterInterface
{
    public function addToQuery(FacetDefinition $facet, BoolQuery $query, Config $config, string $prefix = ''): void;
}
