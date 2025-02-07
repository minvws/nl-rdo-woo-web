<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Facet\Facet;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * This filter can decorate another filter to check those conditions in both main type docs and nested main type docs.
 */
class MainTypesAndNestedMainTypesFilter implements FilterInterface
{
    public function __construct(
        private readonly FilterInterface $subFilter,
    ) {
    }

    public function addToQuery(Facet $facet, BoolQuery $query, SearchParameters $searchParameters, string $prefix = ''): void
    {
        if ($facet->isNotActive()) {
            return;
        }

        $dossierQuery = Query::bool();
        $this->subFilter->addToQuery($facet, $dossierQuery, $searchParameters);

        $nestedDossierQuery = Query::bool();
        $this->subFilter->addToQuery($facet, $nestedDossierQuery, $searchParameters, 'dossiers.');

        $query->addFilter(
            Query::bool(
                should: [
                    Query::bool(
                        filter: [
                            Query::terms(
                                field: 'type',
                                values: ElasticDocumentType::getSubTypeValues(),
                            ),
                            Query::nested(
                                path: 'dossiers',
                                query: $nestedDossierQuery,
                            ),
                        ]
                    ),
                    Query::bool(
                        filter: [
                            Query::terms(
                                field: 'type',
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
