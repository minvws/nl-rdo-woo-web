<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Upload;

use Mockery;
use Mockery\MockInterface;
use PublicationApi\Domain\Upload\UploadValidationService;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadEntityStatus;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;

final class UploadValidationServiceTest extends UnitTestCase
{
    private UploadEntityRepository&MockInterface $uploadEntityRepository;
    private UploadValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadEntityRepository = Mockery::mock(UploadEntityRepository::class);

        $this->service = new UploadValidationService($this->uploadEntityRepository);
    }

    public function testGetValidationErrorsForUploadReturnsEmptyArrayWhenEntityNotFound(): void
    {
        $uploadId = Uuid::v6();

        $this->uploadEntityRepository
            ->expects('findOneBy')
            ->with(['uploadId' => $uploadId->toRfc4122()])
            ->andReturnNull();

        $result = $this->service->getValidationErrorsForUpload($uploadId);

        self::assertSame([], $result);
    }

    public function testGetValidationErrorsForUploadReturnsEmptyArrayWhenStatusIsNotValidationFailed(): void
    {
        $uploadId = Uuid::v6();

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getStatus')->andReturn(UploadEntityStatus::UPLOADED);

        $this->uploadEntityRepository
            ->expects('findOneBy')
            ->with(['uploadId' => $uploadId->toRfc4122()])
            ->andReturn($uploadEntity);

        $result = $this->service->getValidationErrorsForUpload($uploadId);

        self::assertSame([], $result);
    }

    public function testGetValidationErrorsForUploadReturnsEmptyArrayWhenNoErrors(): void
    {
        $uploadId = Uuid::v6();

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getStatus')->andReturn(UploadEntityStatus::VALIDATION_FAILED);
        $uploadEntity->expects('getError')->andReturnNull();

        $this->uploadEntityRepository
            ->expects('findOneBy')
            ->with(['uploadId' => $uploadId->toRfc4122()])
            ->andReturn($uploadEntity);

        $result = $this->service->getValidationErrorsForUpload($uploadId);

        self::assertSame([], $result);
    }

    public function testGetValidationErrorsForUploadReturnsConstraintViolations(): void
    {
        $uploadId = Uuid::v6();

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getStatus')->andReturn(UploadEntityStatus::VALIDATION_FAILED);
        $uploadEntity->expects('getError')->andReturn(['Error one', 'Error two']);

        $this->uploadEntityRepository
            ->expects('findOneBy')
            ->with(['uploadId' => $uploadId->toRfc4122()])
            ->andReturn($uploadEntity);

        $result = $this->service->getValidationErrorsForUpload($uploadId);

        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(ConstraintViolation::class, $result);
        self::assertSame('Error one', $result[0]->getMessage());
        self::assertSame('Error two', $result[1]->getMessage());
    }
}
