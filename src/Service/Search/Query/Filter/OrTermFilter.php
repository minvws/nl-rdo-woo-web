<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\Facet;
use App\Service\Search\Query\Facet\Input\StringValuesFacetInputInterface;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * Meaning that at least one value must match.
 */
class OrTermFilter implements FilterInterface
{
    public function addToQuery(Facet $facet, BoolQuery $query, Config $config, string $prefix = ''): void
    {
        if ($facet->isNotActive()) {
            return;
        }

        $input = $this->getInput($facet);
        if (is_null($input)) {
            return;
        }

        $query->addFilter(
            Query::terms(
                field: $prefix . $facet->getPath(),
                values: $input->getStringValues()
            )
        );
    }

    public function getInput(Facet $facet): ?StringValuesFacetInputInterface
    {
        if ($facet->input instanceof StringValuesFacetInputInterface) {
            return $facet->input;
        }

        return null;
    }
}
