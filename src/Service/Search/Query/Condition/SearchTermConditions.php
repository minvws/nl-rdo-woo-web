<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetList;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class SearchTermConditions implements QueryConditions
{
    public function applyToQuery(FacetList $facetList, Config $config, BoolQuery $query): void
    {
        if ($config->query === '') {
            $query->addShould(Query::matchAll());

            return;
        }

        $query->addShould(
            $this->createDocumentQuery($config)
        );

        $query->addShould(
            $this->createDossierQuery($config)
        );

        $query->setParams(['minimum_should_match' => 1]);
    }

    public function createDocumentQuery(Config $config): BoolQuery
    {
        return Query::bool(
            should: [
                Query::nested(
                    path: 'dossiers',
                    query: Query::bool(
                        should: [
                            Query::simpleQueryString(
                                fields: ['dossiers.title'],
                                query: $config->query,
                            )
                                ->setDefaultOperator($config->operator)
                                ->setBoost(3),
                            Query::simpleQueryString(
                                fields: ['dossiers.summary'],
                                query: $config->query,
                            )
                                ->setDefaultOperator($config->operator)
                                ->setBoost(2),
                        ],
                    )->setParams(['minimum_should_match' => 1])
                ),
                Query::nested(
                    path: 'pages',
                    query: Query::simpleQueryString(
                        fields: ['pages.content'],
                        query: $config->query,
                    )
                        ->setDefaultOperator($config->operator)
                        ->setBoost(1),
                ),
                Query::simpleQueryString(
                    fields: ['filename'],
                    query: $config->query,
                )
                    ->setDefaultOperator($config->operator)
                    ->setBoost(4),
            ],
            filter: [
                Query::term(
                    field: 'type',
                    value: Config::TYPE_DOCUMENT
                ),
            ],
        )->setParams(['minimum_should_match' => 1]);
    }

    public function createDossierQuery(Config $config): BoolQuery
    {
        return Query::bool(
            should: [
                Query::simpleQueryString(
                    fields: ['title'],
                    query: $config->query,
                )
                    ->setDefaultOperator($config->operator)
                    ->setBoost(5),
                Query::simpleQueryString(
                    fields: ['summary'],
                    query: $config->query,
                )
                    ->setDefaultOperator($config->operator)
                    ->setBoost(4),
                Query::simpleQueryString(
                    fields: ['decision_content'],
                    query: $config->query,
                )
                    ->setDefaultOperator($config->operator)
                    ->setBoost(3),
            ],
            filter: [
                Query::term(
                    field: 'type',
                    value: Config::TYPE_DOSSIER,
                ),
            ],
        )->setParams(['minimum_should_match' => 1]);
    }
}
