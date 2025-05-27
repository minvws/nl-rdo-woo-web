<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Definition;

use App\Domain\Search\Query\Facet\FacetListFactory;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Condition\QueryConditionHelper;
use App\Service\Search\Query\Dsl\ElasticQueryParameters;
use Erichard\ElasticQueryBuilder\QueryBuilder;

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
