<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Condition;

use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\Query\NestedQuery;
use Erichard\ElasticQueryBuilder\Query\SimpleQueryStringQuery;
use Erichard\ElasticQueryBuilder\Query\TermQuery;
use Erichard\ElasticQueryBuilder\Query\TermsQuery;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Index\Schema\ElasticNestedField;
use Shared\Domain\Search\Index\Schema\ElasticPath;
use Shared\Domain\Search\Query\Facet\FacetList;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Dsl\Query;
use Shared\Service\Search\Query\Dsl\QueryParameter;

class SearchTermConditionBuilder implements QueryConditionBuilderInterface
{
    public function applyToQuery(FacetList $facetList, SearchParameters $searchParameters, BoolQuery $query): void
    {
        if ($searchParameters->query === '') {
            $query->addShould(Query::matchAll());

            return;
        }

        foreach ($this->createTypeQueries($searchParameters) as $typeQuery) {
            $query->addShould($typeQuery);
        }

        $query->setParams([QueryParameter::MINIMUM_SHOULD_MATCH => 1]);
    }

    public function createDocumentQuery(SearchParameters $searchParameters): BoolQuery
    {
        return Query::bool(
            should: [
                $this->getNestedDossiersTitleAndSummaryQuery($searchParameters),
                $this->getNestedPagesContentQuery($searchParameters),
                $this->getDocumentFilenameQuery($searchParameters),
                $this->getDocumentNumberQuery($searchParameters),
                $this->getDocumentIdQuery($searchParameters),
            ],
            filter: [
                $this->getTypeFilter(),
            ],
        )->setParams([QueryParameter::MINIMUM_SHOULD_MATCH => 1]);
    }

    public function createMainTypesQuery(SearchParameters $searchParameters): BoolQuery
    {
        return Query::bool(
            should: [
                $this->getTitleQuery($searchParameters),
                $this->getSummaryQuery($searchParameters),
                Query::term(
                    field: ElasticField::PREFIXED_DOSSIER_NUMBER->value,
                    value: $searchParameters->query,
                )
                    ->setCaseInsensitive(true)
                    ->setBoost(5),
                Query::term(
                    field: ElasticField::DOSSIER_NUMBER->value,
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
        )->setParams([QueryParameter::MINIMUM_SHOULD_MATCH => 1]);
    }

    public function createSubjectQuery(SearchParameters $searchParameters): BoolQuery
    {
        return Query::bool(
            should: [
                $this->getTitleQuery($searchParameters),
                $this->getSummaryQuery($searchParameters),
                $this->getVisibleContentTreeQuery($searchParameters),
            ],
            filter: [
                Query::terms(
                    field: ElasticField::TYPE->value,
                    values: ElasticDocumentType::getStandaloneTypeValues(),
                ),
            ],
        )->setParams([QueryParameter::MINIMUM_SHOULD_MATCH => 1]);
    }

    /**
     * @return list<BoolQuery>
     */
    protected function createTypeQueries(SearchParameters $searchParameters): array
    {
        return [
            $this->createDocumentQuery($searchParameters),
            $this->createMainTypesQuery($searchParameters),
            $this->createSubjectQuery($searchParameters),
        ];
    }

    protected function getNestedDossiersTitleAndSummaryQuery(SearchParameters $searchParameters): NestedQuery
    {
        return Query::nested(
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
            )->setParams([QueryParameter::MINIMUM_SHOULD_MATCH => 1]),
        );
    }

    protected function getDocumentFilenameQuery(SearchParameters $searchParameters): SimpleQueryStringQuery
    {
        return Query::simpleQueryString(
            fields: [ElasticField::FILENAME->value],
            query: $searchParameters->query,
        )
            ->setDefaultOperator($searchParameters->operator->value)
            ->setBoost(4);
    }

    protected function getDocumentNumberQuery(SearchParameters $searchParameters): TermQuery
    {
        return Query::term(
            field: ElasticField::DOCUMENT_NUMBER->value,
            value: $searchParameters->query,
        )
            ->setCaseInsensitive(true)
            ->setBoost(5);
    }

    protected function getDocumentIdQuery(SearchParameters $searchParameters): TermQuery
    {
        return Query::term(
            field: ElasticField::DOCUMENT_ID->value,
            value: $searchParameters->query,
        )
            ->setCaseInsensitive(true)
            ->setBoost(5);
    }

    protected function getTypeFilter(): TermsQuery
    {
        return Query::terms(
            field: ElasticField::TYPE->value,
            values: ElasticDocumentType::getSubTypeValues(),
        );
    }

    private function getNestedPagesContentQuery(SearchParameters $searchParameters): NestedQuery
    {
        return Query::nested(
            path: ElasticNestedField::PAGES->value,
            query: Query::simpleQueryString(
                fields: [
                    ElasticPath::pagesContent()->value,
                ],
                query: $searchParameters->query,
            )
                ->setDefaultOperator($searchParameters->operator->value)
                ->setBoost(1),
        );
    }

    private function getTitleQuery(SearchParameters $searchParameters): SimpleQueryStringQuery
    {
        return Query::simpleQueryString(
            fields: [ElasticField::TITLE->value],
            query: $searchParameters->query,
        )
            ->setDefaultOperator($searchParameters->operator->value)
            ->setBoost(5);
    }

    private function getSummaryQuery(SearchParameters $searchParameters): SimpleQueryStringQuery
    {
        return Query::simpleQueryString(
            fields: [ElasticField::SUMMARY->value],
            query: $searchParameters->query,
        )
            ->setDefaultOperator($searchParameters->operator->value)
            ->setBoost(4);
    }

    private function getVisibleContentTreeQuery(SearchParameters $searchParameters): BoolQuery
    {
        return Query::bool(
            should: [
                Query::nested(
                    path: ElasticNestedField::CONTENT_TREE->value,
                    query: Query::bool(
                        should: [
                            Query::simpleQueryString(
                                fields: [ElasticPath::contentTreeTitle()->value],
                                query: $searchParameters->query,
                            )
                                ->setDefaultOperator($searchParameters->operator->value)
                                ->setBoost(3),
                            Query::simpleQueryString(
                                fields: [ElasticPath::contentTreeBody()->value],
                                query: $searchParameters->query,
                            )
                                ->setDefaultOperator($searchParameters->operator->value)
                                ->setBoost(2),
                        ],
                    )->setParams([QueryParameter::MINIMUM_SHOULD_MATCH => 1]),
                ),
            ],
            filter: [
                Query::term(
                    field: ElasticField::HAS_VISIBLE_CONTENT_TREE->value,
                    value: true,
                ),
            ],
        )->setParams([QueryParameter::MINIMUM_SHOULD_MATCH => 1]);
    }
}
