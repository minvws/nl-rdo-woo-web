<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Aggregation\DateTermAggregationStrategy;
use App\Service\Search\Query\Aggregation\NestedTermsAggregationStrategy;
use App\Service\Search\Query\Aggregation\TermsAggregationStrategy;
use App\Service\Search\Query\Aggregation\TypeAggregationStrategy;
use App\Service\Search\Query\Filter\AndTermFilter;
use App\Service\Search\Query\Filter\DocTypeFilter;
use App\Service\Search\Query\Filter\MainTypesAndNestedMainTypesFilter;
use App\Service\Search\Query\Filter\OrTermFilter;
use App\Service\Search\Query\Filter\PeriodFilter;
use App\Service\Search\Query\Filter\SubTypesOnlyFilter;

trait HasFacetDefinitions
{
    /**
     * @return list<FacetDefinition>
     */
    public function getDefinitions(): array
    {
        return [
            new FacetDefinition(
                key: FacetKey::TYPE,
                filter: new DocTypeFilter(),
                aggregationStrategy: new TypeAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::SUBJECT,
                filter: new OrTermFilter(),
                aggregationStrategy: new TermsAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::SOURCE,
                filter: new SubTypesOnlyFilter(new OrTermFilter()),
                aggregationStrategy: new TermsAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::GROUNDS,
                filter: new SubTypesOnlyFilter(new AndTermFilter()),
                aggregationStrategy: new TermsAggregationStrategy(false),
            ),
            new FacetDefinition(
                key: FacetKey::JUDGEMENT,
                filter: new SubTypesOnlyFilter(new OrTermFilter()),
                aggregationStrategy: new TermsAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::DEPARTMENT,
                filter: new OrTermFilter(),
                aggregationStrategy: new TermsAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::PERIOD,
                filter: new MainTypesAndNestedMainTypesFilter(new OrTermFilter()),
                aggregationStrategy: new NestedTermsAggregationStrategy('dossiers'),
            ),
            new FacetDefinition(
                key: FacetKey::DATE,
                filter: new PeriodFilter(),
                aggregationStrategy: new DateTermAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::DOSSIER_NR,
                filter: new MainTypesAndNestedMainTypesFilter(new OrTermFilter()),
                // Intentionally no agg. strategy: only exists for filtering
            ),
            new FacetDefinition(
                key: FacetKey::INQUIRY_DOSSIERS,
                // Intentionally no filter, this is handled in ContentAccessConditions
                // Intentionally no agg. strategy: only exists for filtering
            ),
            new FacetDefinition(
                key: FacetKey::INQUIRY_DOCUMENTS,
                // Intentionally no filter, this is handled in ContentAccessConditions
                // Intentionally no agg. strategy: only exists for filtering
            ),
        ];
    }
}
