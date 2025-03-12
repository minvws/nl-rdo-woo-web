<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Query\Facet\Facet;
use App\Domain\Search\Query\SearchParameters;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

interface FilterInterface
{
    public function addToQuery(
        Facet $facet,
        BoolQuery $query,
        SearchParameters $searchParameters,
        ?ElasticNestedField $nestedPath = null,
    ): void;
}
