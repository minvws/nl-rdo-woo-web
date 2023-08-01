<?php

declare(strict_types=1);

namespace App\Service\Search\Query;

use App\Service\Search\Model\Config;
use App\Service\Search\Model\Facet;
use App\Service\Search\Query\DossierStrategy\DossierStrategyInterface;
use App\Service\Search\Query\Filter\DateRangeFilter;
use App\Service\Search\Query\Filter\OrTermFilter;

class DossierQueryGenerator
{
    /**
     * @return array<string, mixed>
     */
    public function getConditions(
        Config $config,
        DossierStrategyInterface $strategy,
    ): array {
        $filterConditions = $this->getFilterConditions($config, $strategy);
        $shouldConditions = $this->getShouldConditions($config, $strategy);

        return $this->getFinalConditions(
            $filterConditions,
            $shouldConditions,
            $strategy->getMinimumShouldMatch(),
        );
    }

    /**
     * @return array<int, mixed>
     */
    private function getFilterConditions(Config $config, DossierStrategyInterface $strategy): array
    {
        $columnMapping = [
            Facet::FACET_DEPARTMENT => new OrTermFilter($strategy->getPath('departments.name')),
            Facet::FACET_OFFICIAL => new OrTermFilter($strategy->getPath('government_officials.name')),
            Facet::FACET_PERIOD => new OrTermFilter($strategy->getPath('period')),
            Facet::FACET_DATE_FROM => new DateRangeFilter($strategy->getPath('date_from'), 'gte'),
            Facet::FACET_DATE_TO => new DateRangeFilter($strategy->getPath('date_to'), 'lte'),
            Facet::FACET_DOSSIER_NR => new OrTermFilter($strategy->getPath('dossier_nr')),
        ];
        $valueMapping = array_intersect_key($config->facets, $columnMapping);

        $mustConditions = [];
        foreach ($valueMapping as $key => $values) {
            $filter = $columnMapping[$key]->getQuery($values);
            if ($filter !== null) {
                $mustConditions[] = $filter;
            }
        }

        if ($strategy->mustTypeCheck()) {
            $mustConditions[] = ['term' => [$strategy->getPath('type') => 'dossier']];
        }

        $validStatuses = $strategy->getStatusValues($config);
        $mustConditions[] = ['terms' => [$strategy->getPath('status') => $validStatuses]];

        // Filtering on inquiries on Dossier layer
        if ($config->dossierInquiries) {
            $mustConditions[] = [
                'terms' => [
                    $strategy->getPath('inquiry_ids') => $config->dossierInquiries,
                ],
            ];
        }

        return $mustConditions;
    }

    /**
     * @return array<int, mixed>
     */
    private function getShouldConditions(Config $config, DossierStrategyInterface $strategy): array
    {
        if ($config->query == '') {
            return [];
        }

        return [
            ['match' => [$strategy->getPath('title') => ['query' => $config->query, 'boost' => 3]]],
            ['match' => [$strategy->getPath('summary') => ['query' => $config->query, 'boost' => 2]]],
        ];
    }

    /**
     * @param array<int, mixed> $filterConditions
     * @param array<int, mixed> $shouldConditions
     *
     * @return array<string, mixed>
     */
    private function getFinalConditions(array $filterConditions, array $shouldConditions, int $minimumShouldMatch): array
    {
        if (empty($filterConditions) && empty($shouldConditions)) {
            return [];
        }

        $conditions = [
            'bool' => [
                'filter' => $filterConditions,
            ],
        ];

        if (! empty($shouldConditions)) {
            $conditions['bool']['should'] = $shouldConditions;
            $conditions['bool']['minimum_should_match'] = min($minimumShouldMatch, count($shouldConditions));
        }

        return $conditions;
    }
}
