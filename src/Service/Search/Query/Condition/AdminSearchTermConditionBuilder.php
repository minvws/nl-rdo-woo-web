<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Index\Schema\ElasticPath;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Dsl\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\Query\NestedQuery;

class AdminSearchTermConditionBuilder extends SearchTermConditionBuilder
{
    #[\Override]
    public function createDocumentQuery(SearchParameters $searchParameters): BoolQuery
    {
        return Query::bool(
            should: [
                $this->getNestedDossiersTitleAndSummaryQuery($searchParameters),
                $this->getDocumentFilenameQuery($searchParameters),
                $this->getDocumentNrQuery($searchParameters),
                $this->getDocumentIdQuery($searchParameters),
                $this->getCaseNrQuery($searchParameters),
            ],
            filter: [
                $this->getTypeFilter(),
            ],
        )->setParams(['minimum_should_match' => 1]);
    }

    #[\Override]
    public function createMainTypesQuery(SearchParameters $searchParameters): BoolQuery
    {
        $query = parent::createMainTypesQuery($searchParameters);
        $query->addShould(
            Query::simpleQueryString(
                fields: [ElasticField::INQUIRY_CASE_NRS->value],
                query: $searchParameters->query,
            )
        );

        return $query;
    }

    private function getCaseNrQuery(SearchParameters $searchParameters): NestedQuery
    {
        return Query::nested(
            path: ElasticNestedField::DOSSIERS->value,
            query: Query::simpleQueryString(
                fields: [
                    ElasticPath::dossiersInquiryCaseNrs()->value,
                ],
                query: $searchParameters->query,
            )
        );
    }
}
