<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchType;
use App\Service\Search\Query\Facet\FacetList;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class ContentAccessConditions implements QueryConditions
{
    public function applyToQuery(FacetList $facetList, SearchParameters $searchParameters, BoolQuery $query): void
    {
        if (empty($searchParameters->dossierInquiries) && ! empty($searchParameters->documentInquiries)) {
            // If a documentInquiries filter is active but no dossierInquiries filter: limit results to documents.
            // Otherwise all dossiers will match as there are no dossier conditions for documentInquiries.
            $searchType = SearchType::DOCUMENT;
        } else {
            $searchType = $searchParameters->searchType;
        }

        switch ($searchType) {
            case SearchType::DOCUMENT:
                $query->addFilter($this->createSubTypesQuery($searchParameters, [ElasticDocumentType::WOO_DECISION_DOCUMENT]));
                break;
            case SearchType::DOSSIER:
                $query->addFilter($this->createTypesQuery($searchParameters, [ElasticDocumentType::WOO_DECISION]));
                break;
            default:
                $query->addFilter(
                    Query::bool(
                        should: $this->getFiltersForAllTypes($searchParameters),
                    )->setParams(['minimum_should_match' => 1])
                );
                break;
        }
    }

    /**
     * @param ElasticDocumentType[] $subTypes
     */
    private function createSubTypesQuery(SearchParameters $searchParameters, array $subTypes): BoolQuery
    {
        $query = Query::bool(
            filter: [
                Query::terms(
                    field: 'type',
                    values: $this->getTypeTerms($subTypes),
                ),
            ],
        );

        if (! empty($searchParameters->dossierInquiries) || ! empty($searchParameters->documentInquiries)) {
            $statuses = [
                DossierStatus::PUBLISHED->value,
                DossierStatus::PREVIEW->value,
            ];

            if (! empty($searchParameters->documentInquiries)) {
                $query->addFilter(
                    Query::terms(
                        field: 'inquiry_ids',
                        values: $searchParameters->documentInquiries
                    )
                );
            }

            if (! empty($searchParameters->dossierInquiries)) {
                $query->addFilter(
                    Query::nested(
                        path: 'dossiers',
                        query: Query::terms(
                            field: 'dossiers.inquiry_ids',
                            values: $searchParameters->dossierInquiries
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
    private function createTypesQuery(SearchParameters $searchParameters, array $types): BoolQuery
    {
        $query = Query::bool(
            filter: [
                Query::terms(
                    field: 'type',
                    values: $this->getTypeTerms($types),
                ),
            ],
        );

        if (! empty($searchParameters->dossierInquiries)) {
            $statuses = [
                DossierStatus::PUBLISHED->value,
                DossierStatus::PREVIEW->value,
            ];
            $query->addFilter(
                Query::terms(
                    field: 'inquiry_ids',
                    values: $searchParameters->dossierInquiries
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
    private function getFiltersForAllTypes(SearchParameters $searchParameters): array
    {
        return [
            $this->createTypesQuery($searchParameters, ElasticDocumentType::getMainTypes()),
            $this->createSubTypesQuery($searchParameters, ElasticDocumentType::getSubTypes()),
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
