<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Filter;

use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Shared\Domain\Search\Index\Schema\ElasticNestedField;
use Shared\Domain\Search\Query\Facet\Facet;
use Shared\Domain\Search\Query\Facet\Input\StringValuesFacetInputInterface;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Dsl\Query;

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
