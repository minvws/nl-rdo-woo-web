<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Department;

use Mockery;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Upload\Department\DepartmentUploadHandler;
use Shared\Domain\Upload\Event\UploadValidatedEvent;
use Shared\Domain\Upload\Process\EntityUploadStorer;
use Shared\Domain\Upload\UploadContextReader;
use Shared\Domain\Upload\UploadEntity;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class DepartmentUploadHandlerTest extends UnitTestCase
{
    public function testHandler(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity
            ->expects('getUploadGroupId')
            ->andReturn(UploadGroupId::DEPARTMENT);

        $departmentId = Uuid::fromString($this->getFaker()->uuid());
        $uploadContextReader = Mockery::mock(UploadContextReader::class);
        $uploadContextReader
            ->expects('getDepartmentId')
            ->with($uploadEntity)
            ->andReturn($departmentId);

        $department = Mockery::mock(Department::class);

        $departmentRepository = Mockery::mock(DepartmentRepository::class);
        $departmentRepository
            ->expects('findOne')
            ->with($departmentId)
            ->andReturn($department);

        $entityUploadStorer = Mockery::mock(EntityUploadStorer::class);
        $entityUploadStorer
            ->expects('storeDepartmentAssetForEntity')
            ->with($uploadEntity, $department);

        $departmentRepository
            ->expects('save')
            ->with($department, true);

        $departmentUploadHandler = new DepartmentUploadHandler(
            $departmentRepository,
            $entityUploadStorer,
            $uploadContextReader,
        );
        $departmentUploadHandler->__invoke(new UploadValidatedEvent($uploadEntity));
    }

    public function testWithInvalidUploadGroupd(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity
            ->expects('getUploadGroupId')
            ->andReturn(UploadGroupId::MAIN_DOCUMENTS);

        $departmentUploadHandler = new DepartmentUploadHandler(
            Mockery::mock(DepartmentRepository::class),
            Mockery::mock(EntityUploadStorer::class),
            Mockery::mock(UploadContextReader::class),
        );
        $departmentUploadHandler->__invoke(new UploadValidatedEvent($uploadEntity));
    }
}
