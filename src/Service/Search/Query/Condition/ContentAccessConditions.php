<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Enum\PublicationStatus;
use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetList;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class ContentAccessConditions implements QueryConditions
{
    public function applyToQuery(FacetList $facetList, Config $config, BoolQuery $query): void
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
                    Query::bool(
                        should: [
                            $this->createDocumentQuery($config),
                            $this->createDossierQuery($config),
                        ],
                    )->setParams(['minimum_should_match' => 1])
                );
                break;
        }
    }

    private function createDocumentQuery(Config $config): BoolQuery
    {
        $query = Query::bool(
            filter: [
                Query::term(
                    field: 'type',
                    value: Config::TYPE_DOCUMENT
                ),
            ],
        );

        if (! empty($config->dossierInquiries) || ! empty($config->documentInquiries)) {
            $statuses = [
                PublicationStatus::PUBLISHED->value,
                PublicationStatus::PREVIEW->value,
            ];

            if (! empty($config->documentInquiries)) {
                $query->addFilter(
                    Query::terms(
                        field: 'inquiry_ids',
                        values: $config->documentInquiries
                    )
                );
            }
        } else {
            $statuses = [
                PublicationStatus::PUBLISHED->value,
            ];
        }

        $query->addFilter(
            Query::nested(
                path: 'dossiers',
                query: Query::terms(
                    field: 'dossiers.status',
                    values: $statuses,
                )
            )
        );

        return $query;
    }

    private function createDossierQuery(Config $config): BoolQuery
    {
        $query = Query::bool(
            filter: [
                Query::term(
                    field: 'type',
                    value: Config::TYPE_DOSSIER
                ),
            ],
        );

        if (! empty($config->dossierInquiries)) {
            $statuses = [
                PublicationStatus::PUBLISHED->value,
                PublicationStatus::PREVIEW->value,
            ];
            $query->addFilter(
                Query::terms(
                    field: 'inquiry_ids',
                    values: $config->dossierInquiries
                ),
            );
        } else {
            $statuses = [
                PublicationStatus::PUBLISHED->value,
            ];
        }

        $query->addFilter(
            Query::terms(
                field: 'status',
                values: $statuses,
            )
        );

        return $query;
    }
}
