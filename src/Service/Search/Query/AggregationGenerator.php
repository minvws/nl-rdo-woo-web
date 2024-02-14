<?php

declare(strict_types=1);

namespace App\Service\Search\Query;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Condition\ContentAccessConditions;
use App\Service\Search\Query\Condition\FacetConditions;
use App\Service\Search\Query\Condition\SearchTermConditions;
use App\Service\Search\Query\Facet\Facet;
use App\Service\Search\Query\Facet\FacetList;
use Erichard\ElasticQueryBuilder\Aggregation\FilterAggregation;
use Erichard\ElasticQueryBuilder\QueryBuilder;

class AggregationGenerator
{
    public function __construct(
        private readonly ContentAccessConditions $accessConditions,
        private readonly FacetConditions $facetConditions,
        private readonly SearchTermConditions $searchTermConditions,
    ) {
    }

    public function addAggregations(FacetList $facetList, QueryBuilder $queryBuilder, Config $config, int $maxCount): void
    {
        if (! $config->aggregations) {
            return;
        }

        // First split the facets into two groups: 'facets affected by filters' and 'regular facets'.
        // All facets that have no selected value(s) in the Config object are not affected by filters.
        // Additionally, some aggregations don't exclude their own filters (AND) so are also not affected by filters.
        /** @var list<Facet> $regularFacets */
        $regularFacets = [];
        /** @var list<Facet> $filterAffectedFacets */
        $filterAffectedFacets = [];
        foreach ($facetList as $facet) {
            if ($facet->isActive() && $facet->shouldExcludeOwnFilter()) {
                $filterAffectedFacets[] = $facet;
            } else {
                $regularFacets[] = $facet;
            }
        }

        // Regular facets are not affected by facet filters, so can be added directly
        foreach ($regularFacets as $facet) {
            $aggregation = $facet->getOptionalAggregation($facet, $config, $maxCount);
            if (! is_null($aggregation)) {
                $queryBuilder->addAggregation($aggregation);
            }
        }

        // Filter affected facets need special handling to exclude their own filter.
        // Unfortunately ES has no tag/exclude mechanism, so we need to exclude the main query/filter and set specific
        // filters to apply per facet.
        if (count($filterAffectedFacets) > 0) {
            // Add a 'global' aggregation, this basically excludes all main query conditions
            $globalAggregation = Aggregation::global('all');

            // Because the main query is excluded we need to re-apply all non-facet conditions.
            // As a small optimization we can do this for all active facets at once, instead of repeating for each.
            $baseConditionsQuery = Query::bool();
            $this->accessConditions->applyToQuery($facetList, $config, $baseConditionsQuery);
            $this->searchTermConditions->applyToQuery($facetList, $config, $baseConditionsQuery);
            $baseFilterAgg = Aggregation::filter(
                name: 'facet-base-filter',
                query: $baseConditionsQuery,
            );

            // Now add aggregations for all active facets within the base filter aggregation
            foreach ($filterAffectedFacets as $facet) {
                $this->addAggregationToParent($facetList, $facet, $config, $baseFilterAgg, $maxCount);
            }

            $globalAggregation->addAggregation($baseFilterAgg);
            $queryBuilder->addAggregation($globalAggregation);
        }
    }

    public function addDocTypeAggregations(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->addAggregation(
            Aggregation::cardinality(
                nameAndField: 'unique_dossiers',
                fieldOrSource: 'dossier_nr',
            )->setPrecisionThreshold(40_000),
        );

        $queryBuilder->addAggregation(
            Aggregation::cardinality(
                nameAndField: 'unique_documents',
                fieldOrSource: 'document_nr',
            )->setPrecisionThreshold(40_000),
        );
    }

    private function addAggregationToParent(
        FacetList $facetList,
        Facet $facet,
        Config $config,
        FilterAggregation $parentAggregation,
        int $maxCount
    ): void {
        // Some facet definitions have no strategy (only used for filtering, not actual faceting). In that case skip.
        $aggregation = $facet->getOptionalAggregation($facet, $config, $maxCount);
        if (is_null($aggregation)) {
            return;
        }

        $filterQuery = Query::bool();

        // Apply the filters of all other active facets to this one, except its own filter.
        $this->facetConditions->applyToQuery($facetList, $config, $filterQuery, $facet->getFacetKey());

        // If there are no other facet filter no filter sub-query is needed, directly add the aggregation.
        if ($filterQuery->isEmpty()) {
            $parentAggregation->addAggregation($aggregation);

            return;
        }

        // Wrap the aggregation with the filters for the other active facets and add it to the parent aggregation
        $parentAggregation->addAggregation(
            Aggregation::filter(
                name: 'facet-filter-' . $facet->getFacetKey()->value,
                query: $filterQuery,
            )->setAggregations([$aggregation])
        );
    }
}
