<?php

declare(strict_types=1);

namespace App\Service\Search\Query;

use App\Service\Search\Model\Config;
use App\Service\Search\Model\Facet;
use App\Service\Search\Query\DossierStrategy\NestedDossierStrategy;
use App\Service\Search\Query\Filter\AndTermFilter;
use App\Service\Search\Query\Filter\OrTermFilter;

class DocumentQueryGenerator
{
    public function __construct(protected DossierQueryGenerator $dosQueryGen)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getConditions(Config $config): array
    {
        $filterConditions = $this->getFilterConditions($config);
        $mustConditions = $this->getMustConditions($config);

        return $this->getFinalConditions($filterConditions, $mustConditions);
    }

    /**
     * @example output
     *
     * [
     *     {
     *         "terms": {
     *             "source_type": ["pdf"]
     *         }
     *     },
     *     {
     *         "term": {
     *             "type": "document"
     *         }
     *     },
     * ],
     *
     * @return array<int, mixed>
     */
    private function getFilterConditions(Config $config): array
    {
        $columnMapping = [
            Facet::FACET_SUBJECT => new AndTermFilter('subjects'),
            Facet::FACET_SOURCE => new OrTermFilter('source_type'),
            Facet::FACET_GROUNDS => new OrTermFilter('grounds'),
            Facet::FACET_JUDGEMENT => new OrTermFilter('judgement'),
        ];
        $valueMapping = array_intersect_key($config->facets, $columnMapping);

        $mustConditions = [];
        foreach ($valueMapping as $key => $values) {
            $filter = $columnMapping[$key]->getQuery($values);
            if ($filter !== null) {
                $mustConditions[] = $filter;
            }
        }

        $mustConditions[] = ['term' => ['type' => Config::TYPE_DOCUMENT]];

        // Filtering on inquiries on Document layer
        if ($config->documentInquiries) {
            $mustConditions[] = ['terms' => ['inquiry_ids' => $config->documentInquiries]];
        }

        return $mustConditions;
    }

    /**
     * @return array<int, mixed>
     */
    private function getMustConditions(Config $config): array
    {
        $mustConditions = [];

        // Nested dossiers query
        // Which will check if the document has a valid dossier
        // We use the same dossierQueryGenerator with a prefix to the field keys
        $dosQueryCon = $this->dosQueryGen->getConditions(
            $config,
            new NestedDossierStrategy(),
        );
        $mustConditions[] = [
            'nested' => [
                'path' => 'dossiers',
                'query' => $dosQueryCon,
            ],
        ];

        // Adds the text search against the content of the nested pages
        if ($config->query != '') {
            $mustConditions[] = [
                'nested' => [
                    'path' => 'pages',
                    'query' => [
                        'match' => [
                            'pages.content' => [
                                'query' => $config->query,
                                'boost' => 1,
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $mustConditions;
    }

    /**
     * @param array<int, mixed> $filterConfitions
     * @param array<int, mixed> $mustConditions
     *
     * @return array<string, mixed>
     */
    private function getFinalConditions(array $filterConfitions, array $mustConditions): array
    {
        if (empty($filterConfitions) && empty($mustConditions)) {
            return [];
        }

        return [
            'bool' => [
                'filter' => $filterConfitions,
                'must' => $mustConditions,
            ],
        ];
    }
}
