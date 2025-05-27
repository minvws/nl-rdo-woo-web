<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Index\Schema\ElasticPath;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Dsl\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class AdminSearchTermConditionBuilder extends SearchTermConditionBuilder
{
    #[\Override]
    public function createDocumentQuery(SearchParameters $searchParameters): BoolQuery
    {
        $query = parent::createDocumentQuery($searchParameters);
        $query->addShould(
            Query::nested(
                path: ElasticNestedField::DOSSIERS->value,
                query: Query::simpleQueryString(
                    fields: [
                        ElasticPath::dossiersInquiryCaseNrs()->value,
                    ],
                    query: $searchParameters->query,
                )
            )
        );

        return $query;
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
}
