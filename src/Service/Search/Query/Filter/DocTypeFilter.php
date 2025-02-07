<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\ElasticField;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Facet\Facet;
use App\Service\Search\Query\Facet\Input\StringValuesFacetInputInterface;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * Meaning that at least one value must match.
 */
class DocTypeFilter implements FilterInterface
{
    public function addToQuery(Facet $facet, BoolQuery $query, SearchParameters $searchParameters, string $prefix = ''): void
    {
        if ($facet->isNotActive()) {
            return;
        }

        $input = $this->getInput($facet);
        if (is_null($input)) {
            return;
        }

        $subFilters = [];
        foreach ($input->getStringValues() as $value) {
            $valueParts = explode('.', $value);
            if (count($valueParts) !== 2) {
                continue;
            }

            if ($valueParts[1] === 'publication') {
                $subFilters[] = Query::bool(
                    must: [
                        Query::term(
                            field: ElasticField::TOPLEVEL_TYPE->value,
                            value: $valueParts[0],
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
                            value: $valueParts[0],
                        ),
                        Query::term(
                            field: ElasticField::SUBLEVEL_TYPE->value,
                            value: $valueParts[1],
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
