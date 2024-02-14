<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetList;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

interface QueryConditions
{
    public function applyToQuery(FacetList $facetList, Config $config, BoolQuery $query): void;
}
