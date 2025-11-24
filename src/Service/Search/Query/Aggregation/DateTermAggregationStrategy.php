<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Aggregation;

use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Shared\Domain\Search\Query\Facet\Facet;
use Shared\Domain\Search\Query\Facet\Input\DateFacetInputInterface;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Dsl\Aggregation;
use Shared\Service\Search\Query\Filter\PeriodFilter;

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
