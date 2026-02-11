<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Department;

use Exception;
use Mockery;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Department\UpdateDepartmentCommand;
use Shared\Domain\Department\UpdateDepartmentHandler;
use Shared\Domain\Search\Index\Updater\DepartmentIndexUpdater;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class UpdateDepartmentHandlerTest extends UnitTestCase
{
    public function testInvokeWithDepartment(): void
    {
        $uuid = Uuid::v4();
        $department = new Department();

        $departmentRepository = Mockery::mock(DepartmentRepository::class);
        $departmentRepository->expects('find')
            ->with($uuid)
            ->andReturn($department);
        $departmentIndexUpdater = Mockery::mock(DepartmentIndexUpdater::class);
        $departmentIndexUpdater->expects('update')
            ->with($department);
        $logger = Mockery::mock(LoggerInterface::class);

        $message = new UpdateDepartmentCommand($uuid);

        (new UpdateDepartmentHandler($departmentRepository, $departmentIndexUpdater, $logger))($message);
    }

    public function testInvokeWhenDepartmentNotFound(): void
    {
        $uuid = Uuid::v4();

        $departmentRepository = Mockery::mock(DepartmentRepository::class);
        $departmentRepository->expects('find')
            ->with($uuid)
            ->andReturn(null);
        $departmentIndexUpdater = Mockery::mock(DepartmentIndexUpdater::class);
        $logger = Mockery::mock(LoggerInterface::class);

        $message = new UpdateDepartmentCommand($uuid);

        self::expectException(RuntimeException::class);
        (new UpdateDepartmentHandler($departmentRepository, $departmentIndexUpdater, $logger))($message);
    }

    public function testInvokeWithDepartmentFials(): void
    {
        $uuid = Uuid::v4();
        $department = new Department();

        $departmentRepository = Mockery::mock(DepartmentRepository::class);
        $departmentRepository->expects('find')
            ->with($uuid)
            ->andReturn($department);
        $departmentIndexUpdater = Mockery::mock(DepartmentIndexUpdater::class);
        $departmentIndexUpdater->expects('update')
            ->with($department)
            ->andThrow(new Exception());
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('error');

        $message = new UpdateDepartmentCommand($uuid);

        (new UpdateDepartmentHandler($departmentRepository, $departmentIndexUpdater, $logger))($message);
    }
}
