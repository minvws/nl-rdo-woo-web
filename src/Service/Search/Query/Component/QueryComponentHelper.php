<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Component;

use Erichard\ElasticQueryBuilder\QueryBuilder;
use Shared\Domain\Search\Query\Facet\FacetList;
use Shared\Domain\Search\Query\SearchParameters;

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
