<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Component;

use Erichard\ElasticQueryBuilder\QueryBuilder;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Service\Search\Query\Aggregation\AggregationName;
use Shared\Service\Search\Query\Dsl\Aggregation;

class UniqueDossierCountComponent
{
    public function apply(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->addAggregation(
            Aggregation::cardinality(
                nameAndField: AggregationName::UNIQUE_DOSSIERS,
                fieldOrSource: ElasticField::PREFIXED_DOSSIER_NUMBER->value,
            )->setPrecisionThreshold(40_000),
        );
    }
}
