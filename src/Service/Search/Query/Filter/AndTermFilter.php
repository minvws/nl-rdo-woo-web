<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Query\Facet\Facet;
use App\Domain\Search\Query\Facet\Input\StringValuesFacetInputInterface;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Dsl\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * Meaning that all values must match.
 */
class AndTermFilter implements FilterInterface
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

        foreach ($input->getStringValues() as $value) {
            $query->addFilter(
                Query::term(
                    field: ($nestedPath ? $nestedPath->value . '.' : '') . $facet->getPath(),
                    value: $value
                )
            );
        }
    }

    public function getInput(Facet $facet): ?StringValuesFacetInputInterface
    {
        if ($facet->input instanceof StringValuesFacetInputInterface) {
            return $facet->input;
        }

        return null;
    }
}
