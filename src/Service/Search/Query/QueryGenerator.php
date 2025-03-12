<?php

declare(strict_types=1);

namespace App\Service\Search\Query;

use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Index\Schema\ElasticPath;
use App\Domain\Search\Query\Facet\FacetList;
use App\Domain\Search\Query\Facet\FacetListFactory;
use App\Domain\Search\Query\SearchParameters;
use App\ElasticConfig;
use App\Service\Search\Query\Condition\ContentAccessConditions;
use App\Service\Search\Query\Condition\FacetConditions;
use App\Service\Search\Query\Condition\QueryConditions;
use App\Service\Search\Query\Condition\SearchTermConditions;
use App\Service\Search\Query\Sort\SortField;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\QueryBuilder;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
readonly class QueryGenerator
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

    public function createFacetsQuery(SearchParameters $searchParameters): QueryBuilder
    {
        $facetList = $this->facetListFactory->fromFacetInputs($searchParameters->facetInputs);

        $queryBuilder = new QueryBuilder();
        $queryBuilder->setIndex(ElasticConfig::READ_INDEX);
        $queryBuilder->setSize(0);
        $queryBuilder->setSource(false);

        $this->addQuery($facetList, $queryBuilder, $searchParameters);

        $this->aggregationGenerator->addAggregations($facetList, $queryBuilder, $searchParameters, 5);

        return $queryBuilder;
    }

    public function createExtendedFacetsQuery(SearchParameters $searchParameters): QueryBuilder
    {
        $facetList = $this->facetListFactory->fromFacetInputs($searchParameters->facetInputs);

        $queryBuilder = new QueryBuilder();
        $queryBuilder->setIndex(ElasticConfig::READ_INDEX);
        $queryBuilder->setSize(0);
        $queryBuilder->setSource(false);

        $this->addQuery($facetList, $queryBuilder, $searchParameters);
        $this->aggregationGenerator->addAggregations($facetList, $queryBuilder, $searchParameters, 25);

        return $queryBuilder;
    }

    public function createQuery(SearchParameters $searchParameters): QueryBuilder
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->setIndex(ElasticConfig::READ_INDEX);
        $queryBuilder->setSize($searchParameters->limit);
        $queryBuilder->setFrom($searchParameters->offset);

        $params = [
            'body' => [
                'docvalue_fields' => [
                    ElasticField::TYPE->value,
                    ElasticField::DOCUMENT_NR->value,
                    ElasticField::DOCUMENT_PREFIX->value,
                    ElasticField::DOSSIER_NR->value,
                ],
                '_source' => false,
            ],
        ];

        if ($searchParameters->query !== '') {
            $params['body']['suggest'] = $this->getSuggestParams($searchParameters);
        }

        if ($searchParameters->sortField === SortField::SCORE) {
            $params['body']['sort'] = [
                '_score',
            ];
        } else {
            $params['body']['sort'] = [[
                $searchParameters->sortField->value => [
                    'missing' => '_last',
                    'order' => $searchParameters->sortOrder->value,
                ],
            ]];
        }

        $queryBuilder->setParams($params);

        $facetList = $this->facetListFactory->fromFacetInputs($searchParameters->facetInputs);

        $this->addQuery($facetList, $queryBuilder, $searchParameters);
        $this->aggregationGenerator->addAggregations($facetList, $queryBuilder, $searchParameters, 25);
        $this->aggregationGenerator->addUniqueDossierCountAggregation($queryBuilder);
        $this->addHighlight($queryBuilder, $searchParameters);

        if ($searchParameters->baseQueryConditions instanceof QueryConditions) {
            /** @var BoolQuery $query */
            $query = $queryBuilder->getQuery();
            $searchParameters->baseQueryConditions->applyToQuery($facetList, $searchParameters, $query);
        }

        return $queryBuilder;
    }

    private function addQuery(FacetList $facetList, QueryBuilder $queryBuilder, SearchParameters $searchParameters): void
    {
        $query = Query::bool();

        $this->accessConditions->applyToQuery($facetList, $searchParameters, $query);
        $this->facetConditions->applyToQuery($facetList, $searchParameters, $query);
        $this->searchTermConditions->applyToQuery($facetList, $searchParameters, $query);

        $queryBuilder->setQuery($query);
    }

    /**
     * @return array<string, mixed>
     */
    private function getSuggestParams(SearchParameters $searchParameters): array
    {
        return [
            ElasticConfig::SUGGESTIONS_SEARCH_INPUT => [
                'text' => $searchParameters->query,
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

    private function addHighlight(QueryBuilder $queryBuilder, SearchParameters $searchParameters): void
    {
        if ($searchParameters->query === '') {
            return;
        }

        // Highlighting uses a 'clean' query with additional filters like status.
        // This is very important, otherwise filter values like 'document' and statuses will be highlighted in content.
        $query = Query::simpleQueryString(
            fields: [
                ElasticField::TITLE->value,
                ElasticField::SUMMARY->value,
                ElasticPath::dossiersSummary()->value,
                ElasticPath::dossiersTitle()->value,
                ElasticPath::pagesContent()->value,
            ],
            query: $searchParameters->query,
        )->setDefaultOperator($searchParameters->operator->value);

        $queryBuilder->setHighlight([
            'max_analyzed_offset' => 1000000,
            'pre_tags' => [self::HL_START],
            'post_tags' => [self::HL_END],
            'fields' => [
                // Document object
                ElasticPath::pagesContent()->value => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
                ElasticPath::dossiersTitle()->value => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
                ElasticPath::dossiersSummary()->value => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
                // Dossier object
                ElasticField::TITLE->value => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
                ElasticField::SUMMARY->value => [
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
