<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;

class DocumentConditions
{
    public static function onlyPubliclyAvailable(QueryBuilder $queryBuilder, string $docAlias = 'doc'): QueryBuilder
    {
        $queryBuilder = clone $queryBuilder;

        $queryBuilder->andWhere("$docAlias.judgement IN (:judgements)");
        $queryBuilder->andWhere("$docAlias.suspended != true AND $docAlias.withdrawn != true");
        $queryBuilder->setParameter('judgements', [Judgement::PUBLIC, Judgement::PARTIAL_PUBLIC]);

        return $queryBuilder;
    }

    public static function onlyAlreadyPublic(QueryBuilder $queryBuilder, string $docAlias = 'doc'): QueryBuilder
    {
        $queryBuilder = clone $queryBuilder;

        $queryBuilder->andWhere("$docAlias.judgement = :judgement");
        $queryBuilder->andWhere("$docAlias.suspended != true AND $docAlias.withdrawn != true");
        $queryBuilder->setParameter('judgement', Judgement::ALREADY_PUBLIC);

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
