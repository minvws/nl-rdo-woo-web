<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Condition;

use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Shared\Domain\Search\Query\Facet\FacetList;
use Shared\Domain\Search\Query\SearchParameters;

class BaseQueryConditionBuilder implements QueryConditionBuilderInterface
{
    public function applyToQuery(FacetList $facetList, SearchParameters $searchParameters, BoolQuery $query): void
    {
        if (! $searchParameters->baseQueryConditions instanceof QueryConditionBuilderInterface) {
            return;
        }

        $searchParameters->baseQueryConditions->applyToQuery($facetList, $searchParameters, $query);
    }
}
