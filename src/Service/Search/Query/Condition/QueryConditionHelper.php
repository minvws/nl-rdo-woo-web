<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Condition;

use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\QueryBuilder;
use Shared\Domain\Search\Query\Facet\FacetList;
use Shared\Domain\Search\Query\SearchParameters;

readonly class QueryConditionHelper
{
    public function __construct(
        private ContentAccessConditionBuilder $contentAccessBuilder,
        private FacetConditionBuilder $facetBuilder,
        private SearchTermConditionBuilder $searchTermBuilder,
        private BaseQueryConditionBuilder $baseQueryBuilder,
        private AdminSearchTermConditionBuilder $adminSearchTermBuilder,
        private AdminOrganisationAccessConditionBuilder $adminOrganisationBuilder,
    ) {
    }

    public function addAccessConditionsForPublicSite(
        FacetList $facetList,
        QueryBuilder $queryBuilder,
        SearchParameters $searchParameters,
    ): void {
        $query = $this->getBoolQuery($queryBuilder);
        $this->contentAccessBuilder->applyToQuery($facetList, $searchParameters, $query);
    }

    public function addAccessConditionForAdminOrganisation(
        FacetList $facetList,
        QueryBuilder $queryBuilder,
        SearchParameters $searchParameters,
    ): void {
        $query = $this->getBoolQuery($queryBuilder);
        $this->adminOrganisationBuilder->applyToQuery($facetList, $searchParameters, $query);
    }

    public function addActiveFacetFilterConditions(
        FacetList $facetList,
        QueryBuilder $queryBuilder,
        SearchParameters $searchParameters,
    ): void {
        $query = $this->getBoolQuery($queryBuilder);
        $this->facetBuilder->applyToQuery($facetList, $searchParameters, $query);
    }

    public function addSearchTermConditions(
        FacetList $facetList,
        QueryBuilder $queryBuilder,
        SearchParameters $searchParameters,
    ): void {
        $query = $this->getBoolQuery($queryBuilder);
        $this->searchTermBuilder->applyToQuery($facetList, $searchParameters, $query);
    }

    public function addAdminSearchTermConditions(
        FacetList $facetList,
        QueryBuilder $queryBuilder,
        SearchParameters $searchParameters,
    ): void {
        $query = $this->getBoolQuery($queryBuilder);
        $this->adminSearchTermBuilder->applyToQuery($facetList, $searchParameters, $query);
    }

    public function addBaseQueryConditions(
        FacetList $facetList,
        QueryBuilder $queryBuilder,
        SearchParameters $searchParameters,
    ): void {
        $query = $this->getBoolQuery($queryBuilder);
        $this->baseQueryBuilder->applyToQuery($facetList, $searchParameters, $query);
    }

    private function getBoolQuery(QueryBuilder $queryBuilder): BoolQuery
    {
        $query = $queryBuilder->getQuery();
        if (! $query instanceof BoolQuery) {
            $query = new BoolQuery();
            $queryBuilder->setQuery($query);
        }

        return $query;
    }
}
