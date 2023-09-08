<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetDefinition;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\Query\TermQuery;

/**
 * This filter will match only in root ES documents of the type 'document', excluding for instance 'dossier' ES docs.
 */
class DocumentOnlyFilter implements FilterInterface
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

        $query->addFilter(
            new TermQuery(
                field: 'type',
                value: Config::TYPE_DOCUMENT,
            ),
        );

        $this->subFilter->addToQuery($facet, $query, $config);
    }
}
