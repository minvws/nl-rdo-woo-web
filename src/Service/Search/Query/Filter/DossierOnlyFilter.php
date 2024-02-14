<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\Facet;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * This filter will match only in root ES documents of the type 'dossier', excluding for instance 'document' ES docs.
 * Will also not match on 'dossier' docs nested within 'document' docs!
 */
class DossierOnlyFilter implements FilterInterface
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

        $query->addFilter(
            Query::Term(
                field: 'type',
                value: Config::TYPE_DOSSIER,
            ),
        );

        $this->subFilter->addToQuery($facet, $query, $config);
    }
}
