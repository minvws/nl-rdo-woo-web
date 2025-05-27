<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Index\Schema\ElasticPath;
use App\Domain\Search\Query\Facet\FacetList;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Dsl\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class SearchTermConditionBuilder implements QueryConditionBuilderInterface
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
                    path: ElasticNestedField::DOSSIERS->value,
                    query: Query::bool(
                        should: [
                            Query::simpleQueryString(
                                fields: [
                                    ElasticPath::dossiersTitle()->value,
                                ],
                                query: $searchParameters->query,
                            )
                                ->setDefaultOperator($searchParameters->operator->value)
                                ->setBoost(3),
                            Query::simpleQueryString(
                                fields: [
                                    ElasticPath::dossiersSummary()->value,
                                ],
                                query: $searchParameters->query,
                            )
                                ->setDefaultOperator($searchParameters->operator->value)
                                ->setBoost(2),
                        ],
                    )->setParams(['minimum_should_match' => 1])
                ),
                Query::nested(
                    path: ElasticNestedField::PAGES->value,
                    query: Query::simpleQueryString(
                        fields: [
                            ElasticPath::pagesContent()->value,
                        ],
                        query: $searchParameters->query,
                    )
                        ->setDefaultOperator($searchParameters->operator->value)
                        ->setBoost(1),
                ),
                Query::simpleQueryString(
                    fields: [ElasticField::FILENAME->value],
                    query: $searchParameters->query,
                )
                    ->setDefaultOperator($searchParameters->operator->value)
                    ->setBoost(4),
                Query::term(
                    field: ElasticField::DOCUMENT_NR->value,
                    value: $searchParameters->query,
                )
                    ->setCaseInsensitive(true)
                    ->setBoost(5),
                Query::term(
                    field: ElasticField::DOCUMENT_ID->value,
                    value: $searchParameters->query,
                )
                    ->setCaseInsensitive(true)
                    ->setBoost(5),
            ],
            filter: [
                Query::terms(
                    field: ElasticField::TYPE->value,
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
                    fields: [ElasticField::TITLE->value],
                    query: $searchParameters->query,
                )
                    ->setDefaultOperator($searchParameters->operator->value)
                    ->setBoost(5),
                Query::simpleQueryString(
                    fields: [ElasticField::SUMMARY->value],
                    query: $searchParameters->query,
                )
                    ->setDefaultOperator($searchParameters->operator->value)
                    ->setBoost(4),
                Query::term(
                    field: ElasticField::PREFIXED_DOSSIER_NR->value,
                    value: $searchParameters->query,
                )
                    ->setCaseInsensitive(true)
                    ->setBoost(5),
                Query::term(
                    field: ElasticField::DOSSIER_NR->value,
                    value: $searchParameters->query,
                )
                    ->setCaseInsensitive(true)
                    ->setBoost(5),
            ],
            filter: [
                Query::terms(
                    field: ElasticField::TYPE->value,
                    values: ElasticDocumentType::getMainTypeValues(),
                ),
            ],
        )->setParams(['minimum_should_match' => 1]);
    }
}
