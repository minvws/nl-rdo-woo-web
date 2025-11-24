<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Definition;

use Erichard\ElasticQueryBuilder\QueryBuilder;
use Shared\Domain\Search\Query\SearchParameters;

interface QueryDefinitionInterface
{
    public function configure(QueryBuilder $queryBuilder, SearchParameters $searchParameters): void;
}
