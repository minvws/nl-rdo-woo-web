<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

use App\Domain\Search\Query\Facet\Facet;
use App\Domain\Search\Query\Facet\Input\DateFacetInputInterface;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Dsl\Aggregation;
use App\Service\Search\Query\Filter\PeriodFilter;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;

final readonly class DateTermAggregationStrategy implements AggregationStrategyInterface
{
    public function getAggregation(Facet $facet, SearchParameters $searchParameters, int $maxCount): ?AbstractAggregation
    {
        if ($facet->isNotActive()) {
            return null;
        }

        $input = $this->getInput($facet);
        if (is_null($input)) {
            return null;
        }

        if ($input->isWithoutDate() || ! $input->hasAnyPeriodFilterDates()) {
            return null;
        }

        return Aggregation::filter(
            name: $facet->getPath(),
            query: PeriodFilter::getWithoutDateQuery(),
        );
    }

    public function excludeOwnFilters(): bool
    {
        return true;
    }

    public function getInput(Facet $facet): ?DateFacetInputInterface
    {
        if ($facet->input instanceof DateFacetInputInterface) {
            return $facet->input;
        }

        return null;
    }
}
