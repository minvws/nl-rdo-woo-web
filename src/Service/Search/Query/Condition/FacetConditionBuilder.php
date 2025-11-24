<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Condition;

use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Shared\Domain\Search\Query\Facet\FacetList;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Search\Query\Dsl\Query;

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
