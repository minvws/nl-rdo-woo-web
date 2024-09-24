<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Facet\FacetList;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class FacetConditions implements QueryConditions
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
