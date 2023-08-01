<?php

declare(strict_types=1);

namespace App\Service\Search\Query;

use App\ElasticConfig;
use App\Service\Search\Model\Config;
use App\Service\Search\Model\Facet;
use App\Service\Search\Query\Aggregation\NestedAggregationStrategy;
use App\Service\Search\Query\Aggregation\TermsAggregationStrategy;
use App\Service\Search\Query\DossierStrategy\TopLevelDossierStrategy;

class QueryGenerator
{
    public function __construct(
        protected DocumentQueryGenerator $docQueryGen,
        protected DossierQueryGenerator $dosQueryGen,
        protected Config $config
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function createFacetsQuery(): array
    {
        $query = [
            'index' => ElasticConfig::READ_INDEX,
            'body' => [
                'size' => 0,
                '_source' => false,
                'aggs' => $this->addAggregations(5),
                'query' => $this->addQuery(),
            ],
        ];

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function createExtendedFacetsQuery(): array
    {
        $query = [
            'index' => ElasticConfig::READ_INDEX,
            'body' => [
                'size' => 0,
                '_source' => false,
                'aggs' => $this->addAggregations(25),
            ],
        ];

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function createQuery(): array
    {
        $query = [
            'index' => ElasticConfig::READ_INDEX,
            'body' => [
                'size' => $this->config->limit,
                'from' => $this->config->offset,
                '_source' => [
                    'excludes' => [
                        'content',
                        'pages',
                        'inquiry_ids',
                        'dossiers.inquiry_ids',
                    ],
                ],
                'query' => $this->addQuery(),
                'highlight' => $this->addHighlighting(),
                'aggs' => $this->addAggregations(25),
                'suggest' => $this->addSuggestions(),
            ],
        ];

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    protected function addSuggestions(): array
    {
        $suggestions = [
            ElasticConfig::SUGGESTIONS_SEARCH_INPUT => [
                'text' => $this->config->query,
                'term' => [
                    'field' => 'content_for_suggestions',
                    'size' => 3,
                    'sort' => 'frequency',
                    'suggest_mode' => 'popular',
                    'string_distance' => 'jaro_winkler',
                ],
            ],
        ];

        return $suggestions;
    }

    /**
     * @return array<string, mixed>
     */
    protected function addQuery(): array
    {
        $documentQuery = $this->getDocumentQuery();
        $dossierQuery = $this->getDossierQuery();
        $combinedQuery = $this->combineQueries([$documentQuery, $dossierQuery]);

        return $combinedQuery;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDocumentQuery(): array
    {
        if (! in_array($this->config->searchType, [Config::TYPE_DOCUMENT, Config::TYPE_ALL])) {
            return [];
        }

        $dossierConditions = $this->docQueryGen->getConditions($this->config);

        return $dossierConditions;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDossierQuery(): array
    {
        if (! in_array($this->config->searchType, [Config::TYPE_DOSSIER, Config::TYPE_ALL])) {
            return [];
        }

        $dossierConditions = $this->dosQueryGen->getConditions($this->config, new TopLevelDossierStrategy());

        return $dossierConditions;
    }

    /**
     * @param array<array<string, mixed>> $queries
     *
     * @return array<string, mixed>
     */
    protected function combineQueries(array $queries): array
    {
        $queries = array_values(array_filter($queries));
        $count = count($queries);

        return match ($count) {
            0 => [],
            1 => $queries[0],
            default => [
                'bool' => [
                    'should' => $queries,
                    'minimum_should_match' => 1,
                ],
            ],
        };
    }

    protected function addAggregations(int $maxCount = 5): \stdClass
    {
        if (! $this->config->aggregations) {
            return (object) [];
        }

        // Based on what type we are searching for, we need to aggregate on different fields
        // When searching on dossiers only, we never find aggregations for 'dossiers.departments.name' for instance, but only for 'department.name'
        $aggregationConfig = [
            Config::TYPE_DOCUMENT => [
                new NestedAggregationStrategy('dossiers', 'dossiers', [
                    new TermsAggregationStrategy(Facet::FACET_DEPARTMENT, 'dossiers.departments.name', $maxCount),
                    new TermsAggregationStrategy(Facet::FACET_OFFICIAL, 'dossiers.government_officials.name', $maxCount),
                    new TermsAggregationStrategy(Facet::FACET_PERIOD, 'dossiers.date_period', $maxCount),
                ]),
                new TermsAggregationStrategy(Facet::FACET_SUBJECT, 'subjects', $maxCount),
                new TermsAggregationStrategy(Facet::FACET_SOURCE, 'source_type', $maxCount),
                new TermsAggregationStrategy(Facet::FACET_GROUNDS, 'grounds', $maxCount),
                new TermsAggregationStrategy(Facet::FACET_JUDGEMENT, 'judgement', $maxCount),
            ],
            Config::TYPE_DOSSIER => [
                new TermsAggregationStrategy(Facet::FACET_DEPARTMENT, 'departments.name', $maxCount),
                new TermsAggregationStrategy(Facet::FACET_OFFICIAL, 'government_officials.name', $maxCount),
                new TermsAggregationStrategy(Facet::FACET_PERIOD, 'date_period', $maxCount),
                new TermsAggregationStrategy(Facet::FACET_SUBJECT, 'subjects', $maxCount),
                new TermsAggregationStrategy(Facet::FACET_SOURCE, 'source_type', $maxCount),
                new TermsAggregationStrategy(Facet::FACET_GROUNDS, 'grounds', $maxCount),
                new TermsAggregationStrategy(Facet::FACET_JUDGEMENT, 'judgement', $maxCount),
            ],
        ];
        $aggregationConfig[Config::TYPE_ALL] = $aggregationConfig[Config::TYPE_DOCUMENT];

        $aggregations = [];
        foreach ($aggregationConfig[$this->config->searchType] as $strategy) {
            $queryPart = $strategy->getQuery();
            $aggregations = array_merge_recursive($aggregations, $queryPart);
        }

        $aggregations['unique_dossiers'] = [
            'cardinality' => [
                'field' => 'dossier_nr',
            ],
        ];

        return (object) $aggregations;
    }

    /**
     * @return array<string, mixed>
     */
    protected function addHighlighting(): array
    {
        return [
            'max_analyzed_offset' => 1000000,
            'pre_tags' => ['<span class=\'hl\'>'],
            'post_tags' => ['</span>'],
            'fields' => [
                // Document object
                'pages.content' => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
                'dossiers.title' => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
                'dossiers.summary' => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
                // Dossier object
                'title' => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
                'summary' => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
            ],
            'require_field_match' => false,
        ];
    }
}
