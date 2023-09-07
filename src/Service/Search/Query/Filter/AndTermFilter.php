<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetDefinition;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\Query\TermQuery;

/**
 * Meaning that all values must match.
 */
class AndTermFilter implements FilterInterface
{
    public function addToQuery(FacetDefinition $facet, BoolQuery $query, Config $config, string $prefix = ''): void
    {
        $values = $config->getFacetValues($facet);
        if (count($values) === 0) {
            return;
        }

        foreach ($values as $value) {
            /** @var string $value */
            $query->addFilter(
                new TermQuery(
                    field: $prefix . $facet->getPath(),
                    value: $value
                )
            );
        }
    }
}
