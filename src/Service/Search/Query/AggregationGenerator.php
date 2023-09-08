<?php

declare(strict_types=1);

namespace App\Service\Search\Query;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Condition\ContentAccessConditions;
use App\Service\Search\Query\Condition\FacetConditions;
use App\Service\Search\Query\Condition\SearchTermConditions;
use App\Service\Search\Query\Dsl\GlobalAggregation;
use App\Service\Search\Query\Facet\FacetDefinition;
use App\Service\Search\Query\Facet\FacetMappingService;
use Erichard\ElasticQueryBuilder\Aggregation\CardinalityAggregation;
use Erichard\ElasticQueryBuilder\Aggregation\FilterAggregation;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\QueryBuilder;

class AggregationGenerator
{
    public function __construct(
        private readonly FacetMappingService $facetMapping,
        private readonly ContentAccessConditions $accessConditions,
        private readonly FacetConditions $facetConditions,
        private readonly SearchTermConditions $searchTermConditions,
    ) {
    }

    public function addAggregations(QueryBuilder $queryBuilder, Config $config, int $maxCount): void
    {
        if (! $config->aggregations) {
            return;
        }

        // First split the facets into two groups: 'facets affected by filters' and 'regular facets'.
        // All facets that have no selected value(s) in the Config object are not affected by filters.
        // Additionally, some aggregations don't exclude their own filters (AND) so are also not affected by filters.
        $regularFacets = [];
        $filterAffectedFacets = [];
        foreach ($this->facetMapping->getAll() as $facet) {
            if ($config->hasFacetValues($facet) && $facet->getAggregationStrategy()?->excludeOwnFilters() === true) {
                $filterAffectedFacets[] = $facet;
            } else {
                $regularFacets[] = $facet;
            }
        }

        // Regular facets are not affected by facet filters, so can be added directly
        foreach ($regularFacets as $facet) {
            $aggregation = $facet->getAggregationStrategy()?->getAggregation($facet, $config, $maxCount);
            if ($aggregation) {
                $queryBuilder->addAggregation($aggregation);
            }
        }

        // Filter affected facets need special handling to exclude their own filter.
        // Unfortunately ES has no tag/exclude mechanism, so we need to exclude the main query/filter and set specific
        // filters to apply per facet.
        if (count($filterAffectedFacets) > 0) {
            // Add a 'global' aggregation, this basically excludes all main query conditions
            $globalAggregation = new GlobalAggregation('all');

            // Because the main query is excluded we need to re-apply all non-facet conditions.
            // As a small optimization we can do this for all active facets at once, instead of repeating for each.
            $baseConditionsQuery = new BoolQuery();
            $this->accessConditions->applyToQuery($config, $baseConditionsQuery);
            $this->searchTermConditions->applyToQuery($config, $baseConditionsQuery);
            $baseFilterAgg = new FilterAggregation(
                'facet-base-filter',
                $baseConditionsQuery,
            );

            // Now add aggregations for all active facets within the base filter aggregation
            foreach ($filterAffectedFacets as $facet) {
                $this->addAggregationToParent($facet, $config, $baseFilterAgg, $maxCount);
            }

            $globalAggregation->addAggregation($baseFilterAgg);
            $queryBuilder->addAggregation($globalAggregation);
        }
    }

    public function addDocTypeAggregations(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->addAggregation(
            new CardinalityAggregation('unique_dossiers', 'dossier_nr')
        );

        $queryBuilder->addAggregation(
            new CardinalityAggregation('unique_documents', 'document_nr')
        );
    }

    private function addAggregationToParent(
        FacetDefinition $facet,
        Config $config,
        FilterAggregation $parentAggregation,
        int $maxCount
    ): void {
        // Some facet definitions have no strategy (only used for filtering, not actual faceting). In that case skip.
        $aggregation = $facet->getAggregationStrategy()?->getAggregation($facet, $config, $maxCount);
        if (! $aggregation) {
            return;
        }

        $filterQuery = new BoolQuery();

        // Apply the filters of all other active facets to this one, except its own filter.
        $this->facetConditions->applyToQuery($config, $filterQuery, $facet->getFacetKey());

        // If there are no other facet filter no filter sub-query is needed, directly add the aggregation.
        if ($filterQuery->isEmpty()) {
            $parentAggregation->addAggregation($aggregation);

            return;
        }

        // Wrap the aggregation with the filters for the other active facets and add it to the parent aggregation
        $parentAggregation->addAggregation(
            new FilterAggregation(
                'facet-filter-' . $facet->getFacetKey(),
                $filterQuery,
                [$aggregation]
            )
        );
    }
}
