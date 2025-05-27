<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Component;

use App\Domain\Search\Query\Facet\FacetList;
use App\Domain\Search\Query\SearchParameters;
use Erichard\ElasticQueryBuilder\QueryBuilder;

readonly class QueryComponentHelper
{
    public function __construct(
        private HighlightComponent $highlightComponent,
        private AggregationComponent $aggregationComponent,
        private UniqueDossierCountComponent $uniqueDossierCountComponent,
    ) {
    }

    public function addHighlight(QueryBuilder $queryBuilder, SearchParameters $searchParameters): void
    {
        $this->highlightComponent->apply($queryBuilder, $searchParameters);
    }

    public function addAggregations(
        FacetList $facetList,
        QueryBuilder $queryBuilder,
        SearchParameters $searchParameters,
        int $maxCount,
    ): void {
        $this->aggregationComponent->addAggregations($facetList, $queryBuilder, $searchParameters, $maxCount);
    }

    public function addUniqueDossierCount(QueryBuilder $queryBuilder): void
    {
        $this->uniqueDossierCountComponent->apply($queryBuilder);
    }
}
