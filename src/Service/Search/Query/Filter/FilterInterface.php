<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Facet\Facet;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

interface FilterInterface
{
    public function addToQuery(Facet $facet, BoolQuery $query, SearchParameters $searchParameters, string $prefix = ''): void;
}
