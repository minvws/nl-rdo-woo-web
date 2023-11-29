<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Entity\Dossier;
use Doctrine\ORM\QueryBuilder;

class DocumentConditions
{
    public static function onlyPubliclyAvailable(QueryBuilder $queryBuilder, string $docAlias = 'doc'): QueryBuilder
    {
        $queryBuilder = clone $queryBuilder;

        $queryBuilder->andWhere("$docAlias.judgement IN (:docStatuses)");
        $queryBuilder->andWhere("$docAlias.suspended != true AND $docAlias.withdrawn != true");
        $queryBuilder->setParameter('docStatuses', [Dossier::DECISION_PUBLIC, Dossier::DECISION_PARTIAL_PUBLIC]);

        return $queryBuilder;
    }

    public static function notPubliclyAvailable(QueryBuilder $queryBuilder, string $docAlias = 'doc'): QueryBuilder
    {
        $queryBuilder = clone $queryBuilder;

        $queryBuilder->andWhere("$docAlias.judgement NOT IN (:docStatuses)");
        $queryBuilder->andWhere("$docAlias.suspended != true AND $docAlias.withdrawn != true");
        $queryBuilder->setParameter('docStatuses', [Dossier::DECISION_PUBLIC, Dossier::DECISION_PARTIAL_PUBLIC]);

        return $queryBuilder;
    }

    public static function notOnline(QueryBuilder $queryBuilder, string $docAlias = 'doc'): QueryBuilder
    {
        $queryBuilder = clone $queryBuilder;

        $queryBuilder->andWhere("$docAlias.suspended = true OR $docAlias.withdrawn = true");

        return $queryBuilder;
    }
}
