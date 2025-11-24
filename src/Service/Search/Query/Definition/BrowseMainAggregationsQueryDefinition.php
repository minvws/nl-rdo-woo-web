<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Definition;

use Erichard\ElasticQueryBuilder\QueryBuilder;
use Shared\Domain\Search\Query\Facet\FacetListFactory;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Component\QueryComponentHelper;
use Shared\Service\Search\Query\Condition\QueryConditionHelper;

readonly class BrowseMainAggregationsQueryDefinition implements QueryDefinitionInterface
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

        $this->componentHelper->addAggregations($facetList, $queryBuilder, $searchParameters, 5);
    }
}
