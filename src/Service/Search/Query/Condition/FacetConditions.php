<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetMappingService;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class FacetConditions implements QueryConditions
{
    public function __construct(
        private readonly FacetMappingService $facetMapping,
    ) {
    }

    public function applyToQuery(Config $config, BoolQuery $query, string $facetToSkip = null): void
    {
        foreach ($this->facetMapping->getActiveFacets($config) as $facet) {
            if ($facet->getFacetKey() === $facetToSkip) {
                continue;
            }

            $facetFilterQuery = new BoolQuery();

            $facet->getFilter()?->addToQuery($facet, $facetFilterQuery, $config);

            if (! $facetFilterQuery->isEmpty()) {
                $query->addFilter($facetFilterQuery);
            }
        }
    }
}
