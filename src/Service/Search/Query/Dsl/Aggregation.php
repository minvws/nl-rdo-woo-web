<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Dsl;

use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Aggregation\Aggregation as ErichardAggregation;
use Erichard\ElasticQueryBuilder\Options\Field;
use Erichard\ElasticQueryBuilder\Options\InlineScript;

final class Aggregation extends ErichardAggregation
{
    public static function global(string $name): GlobalAggregation
    {
        return new GlobalAggregation($name);
    }

    /**
     * @param array<AbstractAggregation> $aggregations
     */
    public static function termsWithMinDocCount(
        string $name,
        string|Field|InlineScript $fieldOrSource,
        int $minDocCount,
        array $aggregations = [],
    ): TermsAggregationWithMinDocCount {
        return new TermsAggregationWithMinDocCount($name, $fieldOrSource, $minDocCount, $aggregations);
    }

    public static function missing(string $name, string $field): MissingAggregation
    {
        return new MissingAggregation($name, $field);
    }
}
