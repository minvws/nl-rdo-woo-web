<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Aggregation\DateTermAggregationStrategy;
use App\Service\Search\Query\Aggregation\NestedTermsAggregationStrategy;
use App\Service\Search\Query\Aggregation\TermsAggregationStrategy;
use App\Service\Search\Query\Filter\AndTermFilter;
use App\Service\Search\Query\Filter\DocumentOnlyFilter;
use App\Service\Search\Query\Filter\DossierAndNestedDossierFilter;
use App\Service\Search\Query\Filter\OrTermFilter;
use App\Service\Search\Query\Filter\PeriodFilter;

trait HasFacetDefinitions
{
    /**
     * @return list<FacetDefinition>
     */
    public function getDefinitions(): array
    {
        return [
            new FacetDefinition(
                key: FacetKey::SUBJECT,
                filter: new DocumentOnlyFilter(new OrTermFilter()),
                aggregationStrategy: new TermsAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::SOURCE,
                filter: new DocumentOnlyFilter(new OrTermFilter()),
                aggregationStrategy: new TermsAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::GROUNDS,
                filter: new DocumentOnlyFilter(new AndTermFilter()),
                aggregationStrategy: new TermsAggregationStrategy(false, true),
            ),
            new FacetDefinition(
                key: FacetKey::JUDGEMENT,
                filter: new DocumentOnlyFilter(new OrTermFilter()),
                aggregationStrategy: new TermsAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::DEPARTMENT,
                filter: new DossierAndNestedDossierFilter(new OrTermFilter()),
                aggregationStrategy: new NestedTermsAggregationStrategy('dossiers'),
            ),
            new FacetDefinition(
                key: FacetKey::PERIOD,
                filter: new DossierAndNestedDossierFilter(new OrTermFilter()),
                aggregationStrategy: new NestedTermsAggregationStrategy('dossiers'),
            ),
            new FacetDefinition(
                key: FacetKey::DATE,
                filter: new PeriodFilter(),
                aggregationStrategy: new DateTermAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::DOSSIER_NR,
                filter: new DossierAndNestedDossierFilter(new OrTermFilter()),
                // Intentionally no agg. strategy: only exists for filtering
            ),
            new FacetDefinition(
                key: FacetKey::INQUIRY_DOSSIERS,
                filter: new DossierAndNestedDossierFilter(new OrTermFilter()),
                // Intentionally no agg. strategy: only exists for filtering
            ),
            new FacetDefinition(
                key: FacetKey::INQUIRY_DOCUMENTS,
                filter: new DocumentOnlyFilter(new OrTermFilter()),
                // Intentionally no agg. strategy: only exists for filtering
            ),
        ];
    }
}
