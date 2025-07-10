<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin;

use App\Domain\Department\Department;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\DossierType;
use Doctrine\ORM\QueryBuilder;

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
