<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetDefinition;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\Query\RangeQuery;

class DateRangeFilter implements FilterInterface
{
    public function __construct(
        private readonly string $comparisonOperator
    ) {
    }

    public function addToQuery(FacetDefinition $facet, BoolQuery $query, Config $config, string $prefix = ''): void
    {
        $values = $config->getFacetValues($facet);
        if (count($values) !== 1) {
            return;
        }

        $date = $this->asDate(array_shift($values));
        if ($date === null) {
            return;
        }

        $rangeQuery = new RangeQuery($prefix . $facet->getPath());
        switch ($this->comparisonOperator) {
            case 'lte':
                $rangeQuery->lte($date->format('Y-m-d'));
                break;
            case 'gte':
                $rangeQuery->gte($date->format('Y-m-d'));
                break;
            default:
                throw new \RuntimeException('Unknown DateRangeFilter comparison operator: ' . $this->comparisonOperator);
        }

        $query->addFilter($rangeQuery);
    }

    protected function asDate(mixed $value): ?\DateTimeImmutable
    {
        if (! is_string($value)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
