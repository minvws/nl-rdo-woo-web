<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Query\Facet\Facet;
use App\Domain\Search\Query\Facet\Input\StringValuesFacetInputInterface;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * Meaning that at least one value must match.
 */
class OrTermFilter implements FilterInterface
{
    public function addToQuery(
        Facet $facet,
        BoolQuery $query,
        SearchParameters $searchParameters,
        ?ElasticNestedField $nestedPath = null,
    ): void {
        if ($facet->isNotActive()) {
            return;
        }

        $input = $this->getInput($facet);
        if (is_null($input)) {
            return;
        }

        $query->addFilter(
            Query::terms(
                field: ($nestedPath ? $nestedPath->value . '.' : '') . $facet->getPath(),
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
