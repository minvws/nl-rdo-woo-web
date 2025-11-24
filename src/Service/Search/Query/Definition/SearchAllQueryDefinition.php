<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Definition;

use Erichard\ElasticQueryBuilder\QueryBuilder;
use Shared\Domain\Search\Query\Facet\FacetListFactory;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Component\QueryComponentHelper;
use Shared\Service\Search\Query\Condition\QueryConditionHelper;
use Shared\Service\Search\Query\Dsl\ElasticQueryParameters;

readonly class SearchAllQueryDefinition implements QueryDefinitionInterface
{
    public function __construct(
        private FacetListFactory $facetListFactory,
        private QueryComponentHelper $componentHelper,
        private QueryConditionHelper $conditionHelper,
    ) {
    }

    public function configure(QueryBuilder $queryBuilder, SearchParameters $searchParameters): void
    {
        ElasticQueryParameters::applyTo($queryBuilder, $searchParameters)
            ->withDocvalueFields()
            ->withSuggestParams()
            ->withUserDefinedSort();

        $facetList = $this->facetListFactory->fromFacetInputs($searchParameters->facetInputs);

        $this->conditionHelper->addAccessConditionsForPublicSite($facetList, $queryBuilder, $searchParameters);
        $this->conditionHelper->addActiveFacetFilterConditions($facetList, $queryBuilder, $searchParameters);
        $this->conditionHelper->addSearchTermConditions($facetList, $queryBuilder, $searchParameters);
        $this->conditionHelper->addBaseQueryConditions($facetList, $queryBuilder, $searchParameters);

        $this->componentHelper->addAggregations($facetList, $queryBuilder, $searchParameters, 25);
        $this->componentHelper->addUniqueDossierCount($queryBuilder);
        $this->componentHelper->addHighlight($queryBuilder, $searchParameters);
    }
}
