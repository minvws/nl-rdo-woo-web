<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Index\Schema\ElasticPath;
use App\Domain\Search\Query\Facet\FacetList;
use App\Domain\Search\Query\Facet\Input\StringValuesFacetInput;
use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchType;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class ContentAccessConditions implements QueryConditions
{
    public function applyToQuery(FacetList $facetList, SearchParameters $searchParameters, BoolQuery $query): void
    {
        /** @var StringValuesFacetInput $inquiryDossierFacetInput */
        $inquiryDossierFacetInput = $searchParameters->facetInputs->getByFacetKey(FacetKey::INQUIRY_DOSSIERS);

        /** @var StringValuesFacetInput $inquiryDocumentFacetInput */
        $inquiryDocumentFacetInput = $searchParameters->facetInputs->getByFacetKey(FacetKey::INQUIRY_DOCUMENTS);

        if ($inquiryDossierFacetInput->isNotActive() && $inquiryDocumentFacetInput->isActive()) {
            // If a documentInquiries filter is active but no dossierInquiries filter: limit results to documents.
            // Otherwise all dossiers will match as there are no dossier conditions for documentInquiries.
            $searchType = SearchType::DOCUMENT;
        } else {
            $searchType = $searchParameters->searchType;
        }

        switch ($searchType) {
            case SearchType::DOCUMENT:
                $query->addFilter($this->createSubTypesQuery(
                    $inquiryDossierFacetInput,
                    $inquiryDocumentFacetInput,
                    [ElasticDocumentType::WOO_DECISION_DOCUMENT],
                ));
                break;
            case SearchType::DOSSIER:
                $query->addFilter($this->createTypesQuery(
                    $inquiryDossierFacetInput,
                    [ElasticDocumentType::WOO_DECISION],
                ));
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
    private function createSubTypesQuery(
        StringValuesFacetInput $inquiryDossierFacetInput,
        StringValuesFacetInput $inquiryDocumentFacetInput,
        array $subTypes,
    ): BoolQuery {
        $query = Query::bool(
            filter: [
                Query::terms(
                    field: ElasticField::TYPE->value,
                    values: $this->getTypeTerms($subTypes),
                ),
            ],
        );

        if ($inquiryDossierFacetInput->isActive() || $inquiryDocumentFacetInput->isActive()) {
            $statuses = [
                DossierStatus::PUBLISHED->value,
                DossierStatus::PREVIEW->value,
            ];

            if ($inquiryDocumentFacetInput->isActive()) {
                $query->addFilter(
                    Query::terms(
                        field: ElasticField::INQUIRY_IDS->value,
                        values: $inquiryDocumentFacetInput->getStringValues(),
                    )
                );
            }

            if ($inquiryDossierFacetInput->isActive()) {
                $query->addFilter(
                    Query::nested(
                        path: ElasticNestedField::DOSSIERS->value,
                        query: Query::terms(
                            field: ElasticPath::dossiersInquiryIds()->value,
                            values: $inquiryDossierFacetInput->getStringValues(),
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
                path: ElasticNestedField::DOSSIERS->value,
                query: Query::terms(
                    field: ElasticPath::dossiersStatus()->value,
                    values: $statuses,
                )
            )
        );

        return $query;
    }

    /**
     * @param ElasticDocumentType[] $types
     */
    private function createTypesQuery(StringValuesFacetInput $inquiryDossierFacetInput, array $types): BoolQuery
    {
        $query = Query::bool(
            filter: [
                Query::terms(
                    field: ElasticField::TYPE->value,
                    values: $this->getTypeTerms($types),
                ),
            ],
        );

        if ($inquiryDossierFacetInput->isActive()) {
            $statuses = [
                DossierStatus::PUBLISHED->value,
                DossierStatus::PREVIEW->value,
            ];
            $query->addFilter(
                Query::terms(
                    field: ElasticField::INQUIRY_IDS->value,
                    values: $inquiryDossierFacetInput->getStringValues(),
                ),
            );
        } else {
            $statuses = [
                DossierStatus::PUBLISHED->value,
            ];
        }

        $query->addFilter(
            Query::terms(
                field: ElasticField::STATUS->value,
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
        /** @var StringValuesFacetInput $inquiryDossierFacetInput */
        $inquiryDossierFacetInput = $searchParameters->facetInputs->getByFacetKey(FacetKey::INQUIRY_DOSSIERS);

        /** @var StringValuesFacetInput $inquiryDocumentFacetInput */
        $inquiryDocumentFacetInput = $searchParameters->facetInputs->getByFacetKey(FacetKey::INQUIRY_DOCUMENTS);

        return [
            $this->createTypesQuery($inquiryDossierFacetInput, ElasticDocumentType::getMainTypes()),
            $this->createSubTypesQuery($inquiryDossierFacetInput, $inquiryDocumentFacetInput, ElasticDocumentType::getSubTypes()),
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
