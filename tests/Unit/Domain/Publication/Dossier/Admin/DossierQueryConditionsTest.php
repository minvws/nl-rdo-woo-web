<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Admin;

use App\Domain\Department\Department;
use App\Domain\Publication\Dossier\Admin\DossierQueryConditions;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\DossierType;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\QueryBuilder;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DossierQueryConditionsTest extends MockeryTestCase
{
    private DossierQueryConditions $queryCondition;

    protected function setUp(): void
    {
        $this->queryCondition = new DossierQueryConditions();
    }

    public function testFilterOnStatuses(): void
    {
        $statuses = [DossierStatus::CONCEPT, DossierStatus::PUBLISHED];

        $expression = \Mockery::mock(Func::class);

        $queryBuilder = \Mockery::mock(QueryBuilder::class);
        $queryBuilder->expects('expr->in')->with('dos.status', ':statuses')->andReturn($expression);
        $queryBuilder->expects('andWhere')->with($expression)->andReturnSelf();
        $queryBuilder->expects('setParameter')->with('statuses', $statuses);

        $this->queryCondition->filterOnStatuses($queryBuilder, ...$statuses);
    }

    public function testFilterOnTypes(): void
    {
        $types = [DossierType::COVENANT, DossierType::DISPOSITION];

        $queryBuilder = \Mockery::mock(QueryBuilder::class);
        $queryBuilder->expects('andWhere')->with('dos INSTANCE OF :typeFilters')->andReturnSelf();
        $queryBuilder->expects('setParameter')->with('typeFilters', $types);

        $this->queryCondition->filterOnTypes($queryBuilder, ...$types);
    }

    public function testFilterOnDepartments(): void
    {
        $departments = [
            \Mockery::mock(Department::class),
            \Mockery::mock(Department::class),
        ];

        $expression = \Mockery::mock(Func::class);

        $queryBuilder = \Mockery::mock(QueryBuilder::class);
        $queryBuilder->expects('expr->in')->with('dep.id', ':departments')->andReturn($expression);
        $queryBuilder->expects('innerJoin')->with('dos.departments', 'dep')->andReturnSelf();
        $queryBuilder->expects('andWhere')->with($expression)->andReturnSelf();
        $queryBuilder->expects('setParameter')->with('departments', $departments);

        $this->queryCondition->filterOnDepartments($queryBuilder, ...$departments);
    }
}
