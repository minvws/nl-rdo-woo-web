<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Department;

use App\Domain\Department\Department;
use App\Domain\Department\DepartmentRepository;
use App\Domain\Upload\Department\DepartmentUploadHandler;
use App\Domain\Upload\Event\UploadValidatedEvent;
use App\Domain\Upload\Process\EntityUploadStorer;
use App\Domain\Upload\UploadEntity;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class DepartmentUploadHandlerTest extends UnitTestCase
{
    private DepartmentRepository&MockInterface $departmentRepository;
    private EntityUploadStorer&MockInterface $entityUploadStorer;
    private UploadEntity&MockInterface $uploadEntity;
    private UploadValidatedEvent $event;
    private DepartmentUploadHandler $departmentUploadHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->departmentRepository = \Mockery::mock(DepartmentRepository::class);
        $this->entityUploadStorer = \Mockery::mock(EntityUploadStorer::class);

        $this->departmentUploadHandler = new DepartmentUploadHandler(
            $this->departmentRepository,
            $this->entityUploadStorer,
        );

        $this->uploadEntity = \Mockery::mock(UploadEntity::class);
        $this->event = new UploadValidatedEvent(
            uploadEntity: $this->uploadEntity,
        );
    }

    public function testOnUploadValidated(): void
    {
        $this->uploadEntity
            ->shouldReceive('getUploadGroupId')
            ->once()
            ->andReturn(UploadGroupId::DEPARTMENT);

        $departmentIdString = '1f0535e0-d44d-60c6-a366-c7b8beb4240f';
        $this->uploadEntity
            ->expects('getContext->getString')
            ->once()
            ->with('departmentId')
            ->andReturn($departmentIdString);

        $department = \Mockery::mock(Department::class);

        $this->departmentRepository
            ->shouldReceive('findOne')
            ->once()
            ->with(\Mockery::on(fn (Uuid $uuid): bool => $uuid->toRfc4122() === $departmentIdString))
            ->andReturn($department);

        $this->entityUploadStorer
            ->shouldReceive('storeDepartmentAssetForEntity')
            ->once()
            ->with($this->uploadEntity, $department);

        $this->departmentRepository
            ->shouldReceive('save')
            ->once()
            ->with($department, true);

        $this->departmentUploadHandler->onUploadValidated($this->event);
    }

    public function testOnUploadValidatedWithInvalidUploadGroupd(): void
    {
        $this->uploadEntity
            ->shouldReceive('getUploadGroupId')
            ->once()
            ->andReturn(UploadGroupId::MAIN_DOCUMENTS);

        $this->entityUploadStorer->shouldNotReceive('storeDepartmentAssetForEntity');

        $this->departmentUploadHandler->onUploadValidated($this->event);
    }
}
