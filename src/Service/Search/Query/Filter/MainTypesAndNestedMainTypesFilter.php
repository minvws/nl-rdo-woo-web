<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Query\Facet\Facet;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * This filter can decorate another filter to check those conditions in both main type docs and nested main type docs.
 */
readonly class MainTypesAndNestedMainTypesFilter implements FilterInterface
{
    public function __construct(
        private FilterInterface $subFilter,
    ) {
    }

    public function addToQuery(
        Facet $facet,
        BoolQuery $query,
        SearchParameters $searchParameters,
        ?ElasticNestedField $nestedPath = null,
    ): void {
        if ($facet->isNotActive()) {
            return;
        }

        $dossierQuery = Query::bool();
        $this->subFilter->addToQuery($facet, $dossierQuery, $searchParameters);

        $nestedDossierQuery = Query::bool();
        $this->subFilter->addToQuery($facet, $nestedDossierQuery, $searchParameters, ElasticNestedField::DOSSIERS);

        $query->addFilter(
            Query::bool(
                should: [
                    Query::bool(
                        filter: [
                            Query::terms(
                                field: ElasticField::TYPE->value,
                                values: ElasticDocumentType::getSubTypeValues(),
                            ),
                            Query::nested(
                                path: ElasticNestedField::DOSSIERS->value,
                                query: $nestedDossierQuery,
                            ),
                        ]
                    ),
                    Query::bool(
                        filter: [
                            Query::terms(
                                field: ElasticField::TYPE->value,
                                values: ElasticDocumentType::getMainTypeValues(),
                            ),
                            $dossierQuery,
                        ]
                    ),
                ],
            )->setParams(['minimum_should_match' => 1])
        );
    }
}
