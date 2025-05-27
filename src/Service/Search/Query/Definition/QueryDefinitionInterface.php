<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Definition;

use App\Domain\Search\Query\SearchParameters;
use Erichard\ElasticQueryBuilder\QueryBuilder;

interface QueryDefinitionInterface
{
    public function configure(QueryBuilder $queryBuilder, SearchParameters $searchParameters): void;
}
