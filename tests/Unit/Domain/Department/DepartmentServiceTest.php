<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Department;

use App\Domain\Department\DepartmentService;
use App\Domain\Department\ViewModel\Department;
use App\Domain\Department\ViewModel\DepartmentViewFactory;
use App\Entity\Department as DepartmentEntity;
use App\Repository\DepartmentRepository;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class DepartmentServiceTest extends UnitTestCase
{
    private DepartmentRepository&MockInterface $repository;
    private DepartmentViewFactory&MockInterface $departmentViewFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = \Mockery::mock(DepartmentRepository::class);
        $this->departmentViewFactory = \Mockery::mock(DepartmentViewFactory::class);
    }

    public function testGetPublicDepartments(): void
    {
        $this->repository
            ->shouldReceive('getAllPublicDepartments')
            ->andReturn($departmentEntities = [
                \Mockery::mock(DepartmentEntity::class),
                \Mockery::mock(DepartmentEntity::class),
            ]);

        $this->departmentViewFactory
            ->shouldReceive('makeCollection')
            ->with($departmentEntities)
            ->andReturn($expected = [
                \Mockery::mock(Department::class),
                \Mockery::mock(Department::class),
            ]);

        $actual = (new DepartmentService($this->repository, $this->departmentViewFactory))->getPublicDepartments();

        self::assertSame($expected, $actual);
    }
}
