<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Entity\Judgement;
use Doctrine\ORM\QueryBuilder;

class DocumentConditions
{
    public static function onlyPubliclyAvailable(QueryBuilder $queryBuilder, string $docAlias = 'doc'): QueryBuilder
    {
        $queryBuilder = clone $queryBuilder;

        $queryBuilder->andWhere("$docAlias.judgement IN (:judgements)");
        $queryBuilder->andWhere("$docAlias.suspended != true AND $docAlias.withdrawn != true");
        $queryBuilder->setParameter('judgements', [Judgement::PUBLIC, Judgement::PARTIAL_PUBLIC, Judgement::ALREADY_PUBLIC]);

        return $queryBuilder;
    }

    public static function notPubliclyAvailable(QueryBuilder $queryBuilder, string $docAlias = 'doc'): QueryBuilder
    {
        $queryBuilder = clone $queryBuilder;

        $queryBuilder->andWhere("$docAlias.judgement = :judgement");
        $queryBuilder->andWhere("$docAlias.suspended != true AND $docAlias.withdrawn != true");
        $queryBuilder->setParameter('judgement', Judgement::NOT_PUBLIC);

        return $queryBuilder;
    }

    public static function notOnline(QueryBuilder $queryBuilder, string $docAlias = 'doc'): QueryBuilder
    {
        $queryBuilder = clone $queryBuilder;

        $queryBuilder->andWhere("$docAlias.suspended = true OR $docAlias.withdrawn = true");

        return $queryBuilder;
    }
}
