<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Query\Facet\Facet;
use App\Domain\Search\Query\Facet\Input\DateRangeInputInterface;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Dsl\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class DateRangeFilter implements FilterInterface
{
    public function __construct(
        private readonly string $comparisonOperator,
    ) {
    }

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

        $rangeDate = $input->getDateRangeDate();
        if (is_null($rangeDate)) {
            return;
        }

        $rangeQuery = Query::range(($nestedPath ? $nestedPath->value . '.' : '') . $facet->getPath());
        match ($this->comparisonOperator) {
            'lte' => $rangeQuery->lte($rangeDate),
            'gte' => $rangeQuery->gte($rangeDate),
            default => throw new \RuntimeException('Unknown DateRangeFilter comparison operator: ' . $this->comparisonOperator),
        };

        $query->addFilter($rangeQuery);
    }

    public function getInput(Facet $facet): ?DateRangeInputInterface
    {
        if ($facet->input instanceof DateRangeInputInterface) {
            return $facet->input;
        }

        return null;
    }
}
