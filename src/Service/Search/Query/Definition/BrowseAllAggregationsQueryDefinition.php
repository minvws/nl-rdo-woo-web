<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Definition;

use App\Domain\Search\Query\Facet\FacetListFactory;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Component\QueryComponentHelper;
use App\Service\Search\Query\Condition\QueryConditionHelper;
use Erichard\ElasticQueryBuilder\QueryBuilder;

readonly class BrowseAllAggregationsQueryDefinition implements QueryDefinitionInterface
{
    public function __construct(
        private FacetListFactory $facetListFactory,
        private QueryComponentHelper $componentHelper,
        private QueryConditionHelper $conditionHelper,
    ) {
    }

    public function configure(QueryBuilder $queryBuilder, SearchParameters $searchParameters): void
    {
        $facetList = $this->facetListFactory->fromFacetInputs($searchParameters->facetInputs);

        $this->conditionHelper->addAccessConditionsForPublicSite($facetList, $queryBuilder, $searchParameters);

        $this->componentHelper->addAggregations($facetList, $queryBuilder, $searchParameters, 25);
    }
}
