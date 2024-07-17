<?php

declare(strict_types=1);

namespace App\Service\Search\Query;

use App\ElasticConfig;
use App\Service\Search\Model\Config;
use App\Service\Search\Query\Condition\ContentAccessConditions;
use App\Service\Search\Query\Condition\FacetConditions;
use App\Service\Search\Query\Condition\SearchTermConditions;
use App\Service\Search\Query\Facet\FacetList;
use App\Service\Search\Query\Facet\FacetListFactory;
use Erichard\ElasticQueryBuilder\QueryBuilder;

final readonly class QueryGenerator
{
    public const HL_START = '[[hl_start]]';
    public const HL_END = '[[hl_end]]';

    public function __construct(
        private AggregationGenerator $aggregationGenerator,
        private ContentAccessConditions $accessConditions,
        private FacetConditions $facetConditions,
        private SearchTermConditions $searchTermConditions,
        private FacetListFactory $facetListFactory,
    ) {
    }

    public function createFacetsQuery(Config $config): QueryBuilder
    {
        $facetList = $this->facetListFactory->fromFacetInputs($config->facetInputs);

        $queryBuilder = new QueryBuilder();
        $queryBuilder->setIndex(ElasticConfig::READ_INDEX);
        $queryBuilder->setSize(0);
        $queryBuilder->setSource(false);

        $this->addQuery($facetList, $queryBuilder, $config);

        $this->aggregationGenerator->addAggregations($facetList, $queryBuilder, $config, 5);

        return $queryBuilder;
    }

    public function createExtendedFacetsQuery(Config $config): QueryBuilder
    {
        $facetList = $this->facetListFactory->fromFacetInputs($config->facetInputs);

        $queryBuilder = new QueryBuilder();
        $queryBuilder->setIndex(ElasticConfig::READ_INDEX);
        $queryBuilder->setSize(0);
        $queryBuilder->setSource(false);

        $this->addQuery($facetList, $queryBuilder, $config);
        $this->aggregationGenerator->addAggregations($facetList, $queryBuilder, $config, 25);

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
                'docvalue_fields' => [
                    'type',
                    'document_nr',
                    'document_prefix',
                    'dossier_nr',
                ],
                '_source' => false,
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

        $facetList = $this->facetListFactory->fromFacetInputs($config->facetInputs);

        $this->addQuery($facetList, $queryBuilder, $config);
        $this->aggregationGenerator->addAggregations($facetList, $queryBuilder, $config, 25);
        $this->aggregationGenerator->addUniqueDossierCountAggregation($queryBuilder);
        $this->addHighlight($queryBuilder, $config);

        return $queryBuilder;
    }

    private function addQuery(FacetList $facetList, QueryBuilder $queryBuilder, Config $config): void
    {
        $query = Query::bool();

        $this->accessConditions->applyToQuery($facetList, $config, $query);
        $this->facetConditions->applyToQuery($facetList, $config, $query);
        $this->searchTermConditions->applyToQuery($facetList, $config, $query);

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
        $query = Query::simpleQueryString(
            fields: ['title', 'summary', 'decision_content', 'dossiers.summary', 'dossiers.title', 'pages.content'],
            query: $config->query,
        )->setDefaultOperator($config->operator);

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
