<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Facet\FacetList;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class SearchTermConditions implements QueryConditions
{
    public function applyToQuery(FacetList $facetList, SearchParameters $searchParameters, BoolQuery $query): void
    {
        if ($searchParameters->query === '') {
            $query->addShould(Query::matchAll());

            return;
        }

        $query->addShould(
            $this->createDocumentQuery($searchParameters)
        );

        $query->addShould(
            $this->createMainTypesQuery($searchParameters)
        );

        $query->setParams(['minimum_should_match' => 1]);
    }

    public function createDocumentQuery(SearchParameters $searchParameters): BoolQuery
    {
        return Query::bool(
            should: [
                Query::nested(
                    path: 'dossiers',
                    query: Query::bool(
                        should: [
                            Query::simpleQueryString(
                                fields: ['dossiers.title'],
                                query: $searchParameters->query,
                            )
                                ->setDefaultOperator($searchParameters->operator->value)
                                ->setBoost(3),
                            Query::simpleQueryString(
                                fields: ['dossiers.summary'],
                                query: $searchParameters->query,
                            )
                                ->setDefaultOperator($searchParameters->operator->value)
                                ->setBoost(2),
                        ],
                    )->setParams(['minimum_should_match' => 1])
                ),
                Query::nested(
                    path: 'pages',
                    query: Query::simpleQueryString(
                        fields: ['pages.content'],
                        query: $searchParameters->query,
                    )
                        ->setDefaultOperator($searchParameters->operator->value)
                        ->setBoost(1),
                ),
                Query::simpleQueryString(
                    fields: ['filename'],
                    query: $searchParameters->query,
                )
                    ->setDefaultOperator($searchParameters->operator->value)
                    ->setBoost(4),
            ],
            filter: [
                Query::terms(
                    field: 'type',
                    values: ElasticDocumentType::getSubTypeValues(),
                ),
            ],
        )->setParams(['minimum_should_match' => 1]);
    }

    public function createMainTypesQuery(SearchParameters $searchParameters): BoolQuery
    {
        return Query::bool(
            should: [
                Query::simpleQueryString(
                    fields: ['title'],
                    query: $searchParameters->query,
                )
                    ->setDefaultOperator($searchParameters->operator->value)
                    ->setBoost(5),
                Query::simpleQueryString(
                    fields: ['summary'],
                    query: $searchParameters->query,
                )
                    ->setDefaultOperator($searchParameters->operator->value)
                    ->setBoost(4),
                Query::simpleQueryString(
                    fields: ['decision_content'],
                    query: $searchParameters->query,
                )
                    ->setDefaultOperator($searchParameters->operator->value)
                    ->setBoost(3),
            ],
            filter: [
                Query::terms(
                    field: 'type',
                    values: ElasticDocumentType::getMainTypeValues(),
                ),
            ],
        )->setParams(['minimum_should_match' => 1]);
    }
}
