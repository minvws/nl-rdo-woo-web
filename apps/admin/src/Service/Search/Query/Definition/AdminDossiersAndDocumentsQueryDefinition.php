<?php

declare(strict_types=1);

namespace Admin\Service\Search\Query\Definition;

use Erichard\ElasticQueryBuilder\QueryBuilder;
use Shared\Domain\Search\Query\Facet\FacetListFactory;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Condition\QueryConditionHelper;
use Shared\Service\Search\Query\Definition\QueryDefinitionInterface;
use Shared\Service\Search\Query\Dsl\ElasticQueryParameters;

readonly class AdminDossiersAndDocumentsQueryDefinition implements QueryDefinitionInterface
{
    public function __construct(
        private QueryConditionHelper $conditionHelper,
        private FacetListFactory $facetListFactory,
    ) {
    }

    public function configure(QueryBuilder $queryBuilder, SearchParameters $searchParameters): void
    {
        ElasticQueryParameters::applyTo($queryBuilder, $searchParameters)
            ->withDocvalueFields()
            ->withSortByScore();

        $facetList = $this->facetListFactory->fromFacetInputs($searchParameters->facetInputs);

        $this->conditionHelper->addAccessConditionForAdminOrganisation($facetList, $queryBuilder, $searchParameters);
        $this->conditionHelper->addActiveFacetFilterConditions($facetList, $queryBuilder, $searchParameters);
        $this->conditionHelper->addAdminSearchTermConditions($facetList, $queryBuilder, $searchParameters);
    }
}
