<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Admin;

use Doctrine\ORM\QueryBuilder;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierType;

class DossierQueryConditions
{
    public function filterOnStatuses(QueryBuilder $queryBuilder, DossierStatus ...$statuses): void
    {
        $queryBuilder
            ->andWhere($queryBuilder->expr()->in('dos.status', ':statuses'))->setParameter('statuses', $statuses);
    }

    public function filterOnTypes(QueryBuilder $queryBuilder, DossierType ...$types): void
    {
        $queryBuilder
            ->andWhere('dos INSTANCE OF :typeFilters')->setParameter('typeFilters', $types);
    }

    public function filterOnDepartments(QueryBuilder $queryBuilder, Department ...$departments): void
    {
        $queryBuilder
            ->innerJoin('dos.departments', 'dep')
            ->andWhere($queryBuilder->expr()->in('dep.id', ':departments'))->setParameter('departments', $departments);
    }
}
