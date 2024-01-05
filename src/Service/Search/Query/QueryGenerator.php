<?php

declare(strict_types=1);

namespace App\Service\Search\Query;

use App\ElasticConfig;
use App\Service\Elastic\SimpleQueryStringQuery;
use App\Service\Search\Model\Config;
use App\Service\Search\Query\Condition\ContentAccessConditions;
use App\Service\Search\Query\Condition\FacetConditions;
use App\Service\Search\Query\Condition\SearchTermConditions;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\QueryBuilder;

class QueryGenerator
{
    public const HL_START = '[[hl_start]]';
    public const HL_END = '[[hl_end]]';

    public function __construct(
        private readonly AggregationGenerator $aggregationGenerator,
        private readonly ContentAccessConditions $accessConditions,
        private readonly FacetConditions $facetConditions,
        private readonly SearchTermConditions $searchTermConditions,
    ) {
    }

    public function createFacetsQuery(Config $config): QueryBuilder
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->setIndex(ElasticConfig::READ_INDEX);
        $queryBuilder->setSize(0);
        $queryBuilder->setSource(false);

        $this->addQuery($queryBuilder, $config);

        $this->aggregationGenerator->addAggregations($queryBuilder, $config, 5);

        return $queryBuilder;
    }

    public function createExtendedFacetsQuery(Config $config): QueryBuilder
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->setIndex(ElasticConfig::READ_INDEX);
        $queryBuilder->setSize(0);
        $queryBuilder->setSource(false);

        $this->addQuery($queryBuilder, $config);
        $this->aggregationGenerator->addAggregations($queryBuilder, $config, 25);

        return $queryBuilder;
    }

    public function createQuery(Config $config): QueryBuilder
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->setIndex(ElasticConfig::READ_INDEX);
        $queryBuilder->setSize($config->limit);
        $queryBuilder->setFrom($config->offset);

        $params = [
            'body' => [
                '_source' => [
                    'excludes' => [
                        'content',
                        'pages',
                        'inquiry_ids',
                        'dossiers.inquiry_ids',
                    ],
                ],
            ],
        ];

        if ($config->query !== '') {
            $params['body']['suggest'] = $this->getSuggestParams($config);
        }

        if ($config->sortField === SortField::SCORE) {
            $params['body']['sort'] = [
                '_score',
            ];
        } else {
            $params['body']['sort'] = [[
                $config->sortField->value => [
                    'missing' => '_last',
                    'order' => $config->sortOrder->value,
                ],
            ]];
        }

        $queryBuilder->setParams($params);

        $this->addQuery($queryBuilder, $config);
        $this->aggregationGenerator->addAggregations($queryBuilder, $config, 25);
        $this->aggregationGenerator->addDocTypeAggregations($queryBuilder);
        $this->addHighlight($queryBuilder, $config);

        return $queryBuilder;
    }

    private function addQuery(QueryBuilder $queryBuilder, Config $config): void
    {
        $query = new BoolQuery();

        $this->accessConditions->applyToQuery($config, $query);
        $this->facetConditions->applyToQuery($config, $query);
        $this->searchTermConditions->applyToQuery($config, $query);

        $queryBuilder->setQuery($query);
    }

    /**
     * @return array<string, mixed>
     */
    private function getSuggestParams(Config $config): array
    {
        return [
            ElasticConfig::SUGGESTIONS_SEARCH_INPUT => [
                'text' => $config->query,
                'term' => [
                    'field' => 'content_for_suggestions',
                    'size' => 3,
                    'sort' => 'frequency',
                    'suggest_mode' => 'popular',
                    'string_distance' => 'jaro_winkler',
                ],
            ],
        ];
    }

    private function addHighlight(QueryBuilder $queryBuilder, Config $config): void
    {
        if ($config->query === '') {
            return;
        }

        // Hightlighting uses a 'clean' query with additional filters like status.
        // This is very important, otherwise filter values like 'document' and statuses will be highlighted in content.
        $query = new SimpleQueryStringQuery(
            query: $config->query,
            defaultOperator: $config->operator,
            fields: ['title', 'summary', 'decision_content', 'dossiers.summary', 'dossiers.title', 'pages.content']
        );

        $queryBuilder->setHighlight([
            'max_analyzed_offset' => 1000000,
            'pre_tags' => [self::HL_START],
            'post_tags' => [self::HL_END],
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
                'decision_content' => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
            ],
            'require_field_match' => true,
            'highlight_query' => $query->build(),
        ]);
    }
}
