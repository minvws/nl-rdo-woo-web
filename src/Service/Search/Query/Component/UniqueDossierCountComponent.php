<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Component;

use App\Domain\Search\Index\Schema\ElasticField;
use App\Service\Search\Query\Dsl\Aggregation;
use Erichard\ElasticQueryBuilder\QueryBuilder;

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
