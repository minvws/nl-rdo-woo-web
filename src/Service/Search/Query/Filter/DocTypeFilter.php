<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Query\Facet\Facet;
use App\Domain\Search\Query\Facet\Input\DocTypeValue;
use App\Domain\Search\Query\Facet\Input\StringValuesFacetInputInterface;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Dsl\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * Meaning that at least one value must match.
 */
class DocTypeFilter implements FilterInterface
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

        $subFilters = [];
        foreach ($input->getStringValues() as $value) {
            $docTypeValue = DocTypeValue::fromString($value);
            if ($docTypeValue->getSubType() === null) {
                continue;
            }

            if ($docTypeValue->getSubType() === 'publication') {
                $subFilters[] = Query::bool(
                    must: [
                        Query::term(
                            field: ElasticField::TOPLEVEL_TYPE->value,
                            value: $docTypeValue->getMainType(),
                        ),
                    ],
                    mustNot: [
                        Query::exists(ElasticField::SUBLEVEL_TYPE->value),
                    ],
                );
            } else {
                $subFilters[] = Query::bool(
                    must: [
                        Query::term(
                            field: ElasticField::TOPLEVEL_TYPE->value,
                            value: $docTypeValue->getMainType(),
                        ),
                        Query::term(
                            field: ElasticField::SUBLEVEL_TYPE->value,
                            value: $docTypeValue->getSubType(),
                        ),
                    ],
                );
            }
        }

        $query->addFilter(
            Query::bool(
                should: $subFilters,
            )->setParams(['minimum_should_match' => 1])
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
