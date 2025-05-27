<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Domain\Search\Query\Facet\FacetList;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Dsl\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class FacetConditionBuilder implements QueryConditionBuilderInterface
{
    public function applyToQuery(FacetList $facetList, SearchParameters $searchParameters, BoolQuery $query, ?FacetKey $facetToSkip = null): void
    {
        foreach ($facetList->getActiveFacets() as $facet) {
            if ($facet->getFacetKey() === $facetToSkip) {
                continue;
            }

            $facetFilterQuery = Query::bool();

            $facet->optionallyAddQueryToFilter($facet, $facetFilterQuery, $searchParameters);
            if (! $facetFilterQuery->isEmpty()) {
                $query->addFilter($facetFilterQuery);
            }
        }
    }
}
