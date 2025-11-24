<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Condition;

use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Shared\Domain\Search\Query\Facet\FacetList;
use Shared\Domain\Search\Query\SearchParameters;

interface QueryConditionBuilderInterface
{
    public function applyToQuery(FacetList $facetList, SearchParameters $searchParameters, BoolQuery $query): void;
}
