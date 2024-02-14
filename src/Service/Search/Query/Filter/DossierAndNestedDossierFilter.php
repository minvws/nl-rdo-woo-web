<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\Facet;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * This filter can decorate another filter to check those conditions in both 'root' dossiers and nested dossiers.
 */
class DossierAndNestedDossierFilter implements FilterInterface
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
                            Query::term(
                                field: 'type',
                                value: Config::TYPE_DOCUMENT,
                            ),
                            Query::nested(
                                path: 'dossiers',
                                query: $nestedDossierQuery,
                            ),
                        ]
                    ),
                    Query::bool(
                        filter: [
                            Query::term(
                                field: 'type',
                                value: Config::TYPE_DOSSIER,
                            ),
                            $dossierQuery,
                        ]
                    ),
                ],
            )->setParams(['minimum_should_match' => 1])
        );
    }
}
