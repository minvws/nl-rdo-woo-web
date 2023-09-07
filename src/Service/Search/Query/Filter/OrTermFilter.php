<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetDefinition;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\Query\TermsQuery;

/**
 * Meaning that at least one value must match.
 */
class OrTermFilter implements FilterInterface
{
    public function addToQuery(FacetDefinition $facet, BoolQuery $query, Config $config, string $prefix = ''): void
    {
        /** @var string[] $values */
        $values = $config->getFacetValues($facet);
        if (count($values) === 0) {
            return;
        }

        $query->addFilter(
            new TermsQuery(
                field: $prefix . $facet->getPath(),
                values: $values
            )
        );
    }
}
