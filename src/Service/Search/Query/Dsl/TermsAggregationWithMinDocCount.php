<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Dsl;

use Erichard\ElasticQueryBuilder\Aggregation\TermsAggregation;
use Erichard\ElasticQueryBuilder\Constants\SortDirections;
use Erichard\ElasticQueryBuilder\Options\Field;
use Erichard\ElasticQueryBuilder\Options\InlineScript;

/**
 * The TermsAggregation class does not support adding a min_doc_count value, this adds it.
 */
class TermsAggregationWithMinDocCount extends TermsAggregation
{
    /**
     * @param string[]|string|null $include
     * @param string[]|string|null $exclude
     */
    public function __construct(
        string $name,
        string|Field|InlineScript $fieldOrSource,
        private int $minDocCount,
        array $aggregations = [],
        string $orderField = null,
        string $orderValue = SortDirections::ASC,
        array|string $include = null,
        array|string $exclude = null,
        int $size = 10,
    ) {
        parent::__construct(
            $name,
            $fieldOrSource,
            $aggregations,
            $orderField,
            $orderValue,
            $include,
            $exclude,
            $size
        );
    }

    /**
     * @return array<string, string>
     */
    protected function buildAggregation(): array
    {
        $build = parent::buildAggregation();
        $build['min_doc_count'] = $this->minDocCount;

        return $build;
    }
}
