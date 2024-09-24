<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Facet\FacetList;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

interface QueryConditions
{
    public function applyToQuery(FacetList $facetList, SearchParameters $searchParameters, BoolQuery $query): void;
}
