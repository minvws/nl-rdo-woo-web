<?php

declare(strict_types=1);

namespace App\Service\Search\Query;

use App\Service\Search\Query\Dsl\GlobalAggregation;
use App\Service\Search\Query\Dsl\MissingAggregation;
use App\Service\Search\Query\Dsl\TermsAggregationWithMinDocCount;
use Erichard\ElasticQueryBuilder\Aggregation\Aggregation as ErichardAggregation;
use Erichard\ElasticQueryBuilder\Options\Field;
use Erichard\ElasticQueryBuilder\Options\InlineScript;

final class Aggregation extends ErichardAggregation
{
    public static function global(string $name): GlobalAggregation
    {
        return new GlobalAggregation($name);
    }

    public static function termsWithMinDocCount(
        string $name,
        string|Field|InlineScript $fieldOrSource,
        int $minDocCount,
    ): TermsAggregationWithMinDocCount {
        return new TermsAggregationWithMinDocCount($name, $fieldOrSource, $minDocCount);
    }

    public static function missing(string $name, string $field): MissingAggregation
    {
        return new MissingAggregation($name, $field);
    }
}
