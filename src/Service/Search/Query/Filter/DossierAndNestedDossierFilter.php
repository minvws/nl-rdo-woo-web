<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetDefinition;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\Query\NestedQuery;
use Erichard\ElasticQueryBuilder\Query\TermQuery;

/**
 * This filter can decorate another filter to check those conditions in both 'root' dossiers and nested dossiers.
 */
class DossierAndNestedDossierFilter implements FilterInterface
{
    public function __construct(
        private readonly FilterInterface $subFilter,
    ) {
    }

    public function addToQuery(FacetDefinition $facet, BoolQuery $query, Config $config, string $prefix = ''): void
    {
        /** @var string[] $values */
        $values = $config->getFacetValues($facet);
        if (count($values) === 0) {
            return;
        }

        $dossierQuery = new BoolQuery();
        $this->subFilter->addToQuery($facet, $dossierQuery, $config);

        $nestedDossierQuery = new BoolQuery();
        $this->subFilter->addToQuery($facet, $nestedDossierQuery, $config, 'dossiers.');

        $query->addFilter(
            new BoolQuery(
                should: [
                    new BoolQuery(
                        filter: [
                            new TermQuery(
                                field: 'type',
                                value: Config::TYPE_DOCUMENT,
                            ),
                            new NestedQuery(
                                path: 'dossiers',
                                query: $nestedDossierQuery,
                            ),
                        ]
                    ),
                    new BoolQuery(
                        filter: [
                            new TermQuery(
                                field: 'type',
                                value: Config::TYPE_DOSSIER,
                            ),
                            $dossierQuery,
                        ]
                    ),
                ],
                params: ['minimum_should_match' => 1],
            )
        );
    }
}
