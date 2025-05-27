<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Dsl;

use Erichard\ElasticQueryBuilder\Contracts\QueryInterface;
use Erichard\ElasticQueryBuilder\Query\Query as ErichardQuery;

final class Query extends ErichardQuery
{
    /**
     * @param array<QueryInterface> $must
     * @param array<QueryInterface> $mustNot
     * @param array<QueryInterface> $should
     * @param array<QueryInterface> $filter
     */
    public static function matchAll(
        array $must = [],
        array $mustNot = [],
        array $should = [],
        array $filter = [],
    ): MatchAllQuery {
        return new MatchAllQuery($must, $mustNot, $should, $filter);
    }
}
