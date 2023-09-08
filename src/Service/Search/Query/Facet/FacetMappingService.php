<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

use App\Service\Search\Model\Config;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Aggregation\NestedTermsAggregationStrategy;
use App\Service\Search\Query\Aggregation\TermsAggregationStrategy;
use App\Service\Search\Query\Filter\DocumentOnlyFilter;
use App\Service\Search\Query\Filter\DossierAndNestedDossierFilter;
use App\Service\Search\Query\Filter\DossierOnlyFilter;
use App\Service\Search\Query\Filter\OrTermFilter;
use App\Service\Search\Query\Filter\PeriodFilter;

class FacetMappingService
{
    /**
     * @var FacetDefinition[]|array
     */
    private array $mapping;

    public function __construct()
    {
        $this->mapping = [
            new FacetDefinition(
                key: FacetKey::SUBJECT,
                path: 'subjects',
                queryParam: 'sub',
                filter: new DocumentOnlyFilter(new OrTermFilter()),
                aggregationStrategy: new TermsAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::SOURCE,
                path: 'source_type',
                queryParam: 'src',
                filter: new DocumentOnlyFilter(new OrTermFilter()),
                aggregationStrategy: new TermsAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::GROUNDS,
                path: 'grounds',
                queryParam: 'gnd',
                filter: new DocumentOnlyFilter(new OrTermFilter()),
                aggregationStrategy: new TermsAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::JUDGEMENT,
                path: 'judgement',
                queryParam: 'jdg',
                filter: new DocumentOnlyFilter(new OrTermFilter()),
                aggregationStrategy: new TermsAggregationStrategy(),
            ),
            new FacetDefinition(
                key: FacetKey::DEPARTMENT,
                path: 'departments.name',
                queryParam: 'dep',
                filter: new DossierAndNestedDossierFilter(new OrTermFilter()),
                aggregationStrategy: new NestedTermsAggregationStrategy('dossiers'),
            ),
            new FacetDefinition(
                key: FacetKey::OFFICIAL,
                path: 'government_officials.name',
                queryParam: 'off',
                filter: new DossierAndNestedDossierFilter(new OrTermFilter()),
                aggregationStrategy: new NestedTermsAggregationStrategy('dossiers'),
            ),
            new FacetDefinition(
                key: FacetKey::PERIOD,
                path: 'date_period',
                queryParam: 'prd',
                filter: new DossierAndNestedDossierFilter(new OrTermFilter()),
                aggregationStrategy: new NestedTermsAggregationStrategy('dossiers'),
            ),
            new FacetDefinition(
                key: FacetKey::DATE,
                path: '',   // Paths are not used for period filters
                queryParam: 'dt',
                filter: new PeriodFilter(),
                // Intentionally no agg. strategy: only exists for filtering
            ),
            new FacetDefinition(
                key: FacetKey::DOSSIER_NR,
                path: 'dossier_nr',
                queryParam: 'dnr',
                filter: new DossierAndNestedDossierFilter(new OrTermFilter()),
                // Intentionally no agg. strategy: only exists for filtering
            ),
            new FacetDefinition(
                key: FacetKey::INQUIRY_DOSSIERS,
                path: 'inquiry_ids',
                queryParam: 'dsi',
                filter: new DossierOnlyFilter(new OrTermFilter()),
                // Intentionally no agg. strategy: only exists for filtering
            ),
            new FacetDefinition(
                key: FacetKey::INQUIRY_DOCUMENTS,
                path: 'inquiry_ids',
                queryParam: 'dci',
                filter: new DocumentOnlyFilter(new OrTermFilter()),
                // Intentionally no agg. strategy: only exists for filtering
            ),
        ];
    }

    /**
     * @return FacetDefinition[]
     */
    public function getActiveFacets(Config $config): array
    {
        return array_filter(
            $this->mapping,
            static fn (FacetDefinition $facet): bool => $config->hasFacetValues($facet),
        );
    }

    public function getFacetByKey(string $key): FacetDefinition
    {
        foreach ($this->mapping as $definition) {
            if ($definition->getFacetKey() === $key) {
                return $definition;
            }
        }

        throw new \RuntimeException('Cannot find facet mapping by key ' . $key);
    }

    /**
     * @return FacetDefinition[]
     */
    public function getAll(): array
    {
        return $this->mapping;
    }
}
