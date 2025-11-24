<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Component;

use Erichard\ElasticQueryBuilder\QueryBuilder;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Service\Search\Query\Dsl\Aggregation;

class UniqueDossierCountComponent
{
    public function apply(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->addAggregation(
            Aggregation::cardinality(
                nameAndField: 'unique_dossiers',
                fieldOrSource: ElasticField::PREFIXED_DOSSIER_NR->value,
            )->setPrecisionThreshold(40_000),
        );
    }
}
