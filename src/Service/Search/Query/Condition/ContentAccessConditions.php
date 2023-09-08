<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Entity\Dossier;
use App\Service\Search\Model\Config;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\Query\NestedQuery;
use Erichard\ElasticQueryBuilder\Query\TermQuery;
use Erichard\ElasticQueryBuilder\Query\TermsQuery;

class ContentAccessConditions implements QueryConditions
{
    public function applyToQuery(Config $config, BoolQuery $query): void
    {
        switch ($config->searchType) {
            case Config::TYPE_DOCUMENT:
                $query->addFilter($this->createDocumentQuery($config));
                break;
            case Config::TYPE_DOSSIER:
                $query->addFilter($this->createDossierQuery($config));
                break;
            default:
                $query->addFilter(
                    new BoolQuery(
                        should: [
                            $this->createDocumentQuery($config),
                            $this->createDossierQuery($config),
                        ],
                        params: ['minimum_should_match' => 1],
                    )
                );
                break;
        }
    }

    private function createDocumentQuery(Config $config): BoolQuery
    {
        $query = new BoolQuery(
            filter: [
                new TermQuery(
                    field: 'type',
                    value: Config::TYPE_DOCUMENT
                ),
            ],
        );

        if (! empty($config->dossierInquiries) || ! empty($config->documentInquiries)) {
            $statuses = [
                Dossier::STATUS_PUBLISHED,
                Dossier::STATUS_PREVIEW,
            ];

            if (! empty($config->documentInquiries)) {
                $query->addFilter(
                    new TermsQuery(
                        field: 'inquiry_ids',
                        values: $config->documentInquiries
                    )
                );
            }
        } else {
            $statuses = [
                Dossier::STATUS_PUBLISHED,
            ];
        }

        $query->addFilter(
            new NestedQuery(
                path: 'dossiers',
                query: new TermsQuery(
                    field: 'dossiers.status',
                    values: $statuses,
                )
            )
        );

        return $query;
    }

    private function createDossierQuery(Config $config): BoolQuery
    {
        $query = new BoolQuery(
            filter: [
                new TermQuery(
                    field: 'type',
                    value: Config::TYPE_DOSSIER
                ),
            ],
        );

        if (! empty($config->dossierInquiries)) {
            $statuses = [
                Dossier::STATUS_PUBLISHED,
                Dossier::STATUS_PREVIEW,
            ];
            $query->addFilter(
                new TermsQuery(
                    field: 'inquiry_ids',
                    values: $config->dossierInquiries
                ),
            );
        } else {
            $statuses = [
                Dossier::STATUS_PUBLISHED,
            ];
        }

        $query->addFilter(
            new TermsQuery(
                field: 'status',
                values: $statuses,
            )
        );

        return $query;
    }
}
