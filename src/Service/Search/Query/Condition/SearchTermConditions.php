<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Service\Elastic\SimpleQueryStringQuery;
use App\Service\Search\Model\Config;
use App\Service\Search\Query\Dsl\MatchAllQuery;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\Query\NestedQuery;
use Erichard\ElasticQueryBuilder\Query\TermQuery;

class SearchTermConditions implements QueryConditions
{
    public function applyToQuery(Config $config, BoolQuery $query): void
    {
        if ($config->query === '') {
            $query->addShould(new MatchAllQuery());

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
        return new BoolQuery(
            should: [
                new NestedQuery(
                    path: 'dossiers',
                    query: new BoolQuery(
                        should: [
                            new SimpleQueryStringQuery(
                                query: $config->query,
                                defaultOperator: $config->operator,
                                fields: ['dossiers.title'],
                                boost: 3,
                            ),
                            new SimpleQueryStringQuery(
                                query: $config->query,
                                defaultOperator: $config->operator,
                                fields: ['dossiers.summary'],
                                boost: 2,
                            ),
                        ],
                        params: ['minimum_should_match' => 1],
                    )
                ),
                new NestedQuery(
                    path: 'pages',
                    query: new SimpleQueryStringQuery(
                        query: $config->query,
                        defaultOperator: $config->operator,
                        fields: ['pages.content'],
                        boost: 1,
                    ),
                ),
                new SimpleQueryStringQuery(
                    query: $config->query,
                    defaultOperator: $config->operator,
                    fields: ['filename'],
                    boost: 4,
                ),
            ],
            filter: [
                new TermQuery(
                    field: 'type',
                    value: Config::TYPE_DOCUMENT
                ),
            ],
            params: ['minimum_should_match' => 1]
        );
    }

    public function createDossierQuery(Config $config): BoolQuery
    {
        return new BoolQuery(
            should: [
                new SimpleQueryStringQuery(
                    query: $config->query,
                    defaultOperator: $config->operator,
                    fields: ['title'],
                    boost: 5,
                ),
                new SimpleQueryStringQuery(
                    query: $config->query,
                    defaultOperator: $config->operator,
                    fields: ['summary'],
                    boost: 4,
                ),
                new SimpleQueryStringQuery(
                    query: $config->query,
                    defaultOperator: $config->operator,
                    fields: ['decision_content'],
                    boost: 3,
                ),
            ],
            filter: [
                new TermQuery(
                    field: 'type',
                    value: Config::TYPE_DOSSIER,
                ),
            ],
            params: ['minimum_should_match' => 1]
        );
    }
}
