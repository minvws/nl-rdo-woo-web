<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetList;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class ContentAccessConditions implements QueryConditions
{
    public function applyToQuery(FacetList $facetList, Config $config, BoolQuery $query): void
    {
        if (empty($config->dossierInquiries) && ! empty($config->documentInquiries)) {
            // If a documentInquiries filter is active but no dossierInquiries filter: limit results to documents.
            // Otherwise all dossiers will match as there are no dossier conditions for documentInquiries.
            $searchType = Config::TYPE_DOCUMENT;
        } else {
            $searchType = $config->searchType;
        }

        switch ($searchType) {
            case Config::TYPE_DOCUMENT:
                $query->addFilter($this->createSubTypesQuery($config, [ElasticDocumentType::WOO_DECISION_DOCUMENT]));
                break;
            case Config::TYPE_DOSSIER:
                $query->addFilter($this->createTypesQuery($config, [ElasticDocumentType::WOO_DECISION]));
                break;
            default:
                $query->addFilter(
                    Query::bool(
                        should: $this->getFiltersForAllTypes($config),
                    )->setParams(['minimum_should_match' => 1])
                );
                break;
        }
    }

    /**
     * @param ElasticDocumentType[] $subTypes
     */
    private function createSubTypesQuery(Config $config, array $subTypes): BoolQuery
    {
        $query = Query::bool(
            filter: [
                Query::terms(
                    field: 'type',
                    values: $this->getTypeTerms($subTypes),
                ),
            ],
        );

        if (! empty($config->dossierInquiries) || ! empty($config->documentInquiries)) {
            $statuses = [
                DossierStatus::PUBLISHED->value,
                DossierStatus::PREVIEW->value,
            ];

            if (! empty($config->documentInquiries)) {
                $query->addFilter(
                    Query::terms(
                        field: 'inquiry_ids',
                        values: $config->documentInquiries
                    )
                );
            }

            if (! empty($config->dossierInquiries)) {
                $query->addFilter(
                    Query::nested(
                        path: 'dossiers',
                        query: Query::terms(
                            field: 'dossiers.inquiry_ids',
                            values: $config->dossierInquiries
                        ),
                    )
                );
            }
        } else {
            $statuses = [
                DossierStatus::PUBLISHED->value,
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

    /**
     * @param ElasticDocumentType[] $types
     */
    private function createTypesQuery(Config $config, array $types): BoolQuery
    {
        $query = Query::bool(
            filter: [
                Query::terms(
                    field: 'type',
                    values: $this->getTypeTerms($types),
                ),
            ],
        );

        if (! empty($config->dossierInquiries)) {
            $statuses = [
                DossierStatus::PUBLISHED->value,
                DossierStatus::PREVIEW->value,
            ];
            $query->addFilter(
                Query::terms(
                    field: 'inquiry_ids',
                    values: $config->dossierInquiries
                ),
            );
        } else {
            $statuses = [
                DossierStatus::PUBLISHED->value,
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

    /**
     * @return BoolQuery[]
     */
    private function getFiltersForAllTypes(Config $config): array
    {
        return [
            $this->createTypesQuery($config, ElasticDocumentType::getMainTypes()),
            $this->createSubTypesQuery($config, ElasticDocumentType::getSubTypes()),
        ];
    }

    /**
     * @param ElasticDocumentType[] $types
     *
     * @return string[]
     */
    private function getTypeTerms(array $types): array
    {
        return array_map(
            static fn (ElasticDocumentType $type) => $type->value,
            $types,
        );
    }
}
