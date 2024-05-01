<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Service\Search\Model\Config;
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

    public function addToQuery(Facet $facet, BoolQuery $query, Config $config, string $prefix = ''): void
    {
        if ($facet->isNotActive()) {
            return;
        }

        $dossierQuery = Query::bool();
        $this->subFilter->addToQuery($facet, $dossierQuery, $config);

        $nestedDossierQuery = Query::bool();
        $this->subFilter->addToQuery($facet, $nestedDossierQuery, $config, 'dossiers.');

        $query->addFilter(
            Query::bool(
                should: [
                    Query::bool(
                        filter: [
                            Query::terms(
                                field: 'type',
                                values: array_map(
                                    static fn (ElasticDocumentType $type) => $type->value,
                                    ElasticDocumentType::getSubTypes(),
                                ),
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
                                values: array_map(
                                    static fn (ElasticDocumentType $type) => $type->value,
                                    ElasticDocumentType::getMainTypes(),
                                ),
                            ),
                            $dossierQuery,
                        ]
                    ),
                ],
            )->setParams(['minimum_should_match' => 1])
        );
    }
}
