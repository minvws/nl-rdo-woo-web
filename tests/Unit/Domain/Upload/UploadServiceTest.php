<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

use League\Flysystem\FilesystemOperator;
use Mockery\MockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shared\Domain\Upload\Event\UploadCompletedEvent;
use Shared\Domain\Upload\Event\UploadValidatedEvent;
use Shared\Domain\Upload\Exception\UploadException;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Domain\Upload\Handler\UploadHandlerInterface;
use Shared\Domain\Upload\Result\PartialUploadResult;
use Shared\Domain\Upload\Result\UploadCompletedResult;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadRequest;
use Shared\Domain\Upload\UploadService;
use Shared\Domain\Upload\UploadStatus;
use Shared\Service\Security\User;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Uid\Uuid;

class UploadServiceTest extends UnitTestCase
{
    private UploadHandlerInterface&MockInterface $uploadHandler;
    private EventDispatcherInterface&MockInterface $eventDispatcher;
    private UploadEntityRepository&MockInterface $uploadEntityRepository;
    private Security&MockInterface $security;
    private FilesystemOperator&MockInterface $workingCopyStorage;
    private UploadService $uploadService;

    protected function setUp(): void
    {
        $this->uploadHandler = \Mockery::mock(UploadHandlerInterface::class);
        $this->eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);
        $this->uploadEntityRepository = \Mockery::mock(UploadEntityRepository::class);
        $this->security = \Mockery::mock(Security::class);
        $this->workingCopyStorage = \Mockery::mock(FilesystemOperator::class);

        $this->uploadService = new UploadService(
            $this->uploadHandler,
            $this->eventDispatcher,
            $this->uploadEntityRepository,
            $this->security,
            $this->workingCopyStorage,
        );
    }

    public function testHandleUploadRequestThrowsExceptionForNotGranted(): void
    {
        $request = new UploadRequest(
            2,
            3,
            'foo-bar-123',
            \Mockery::mock(UploadedFile::class),
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag(),
        );

        $this->security->expects('getUser')->andReturn(\Mockery::mock(User::class));
        $this->security->expects('isGranted')->with(UploadService::SECURITY_ATTRIBUTE, $request)->andReturnFalse();

        $this->expectException(UploadException::class);
        $this->uploadService->handleUploadRequest($request);
    }

    public function testHandleUploadRequestThrowsExceptionForAbortedUpload(): void
    {
        $this->security->expects('getUser')->andReturn($user = \Mockery::mock(User::class));
        $this->security->expects('isGranted')->andReturnTrue();

        $request = new UploadRequest(
            2,
            3,
            $uploadId = 'foo-bar-123',
            \Mockery::mock(UploadedFile::class),
            $groupId = UploadGroupId::WOO_DECISION_DOCUMENTS,
            $params = new InputBag(),
        );

        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::ABORTED);

        $this->uploadEntityRepository
            ->expects('findOrCreate')
            ->with($uploadId, $groupId, $user, $params)
            ->andReturn($uploadEntity);

        $this->expectException(UploadException::class);
        $this->uploadService->handleUploadRequest($request);
    }

    public function testHandleUploadRequestWithIncompleteResult(): void
    {
        $this->security->expects('getUser')->andReturn($user = \Mockery::mock(User::class));
        $this->security->expects('isGranted')->andReturnTrue();

        $request = new UploadRequest(
            2,
            3,
            $uploadId = 'foo-bar-123',
            \Mockery::mock(UploadedFile::class),
            $groupId = UploadGroupId::WOO_DECISION_DOCUMENTS,
            $params = new InputBag(),
        );

        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::INCOMPLETE);

        $this->uploadEntityRepository
            ->expects('findOrCreate')
            ->with($uploadId, $groupId, $user, $params)
            ->andReturn($uploadEntity);

        $this->uploadHandler
            ->expects('handleUploadRequest')
            ->with($uploadEntity, $request)
            ->andReturn($result = new PartialUploadResult($uploadId, 'foo.bar', $groupId));

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        self::assertSame(
            $result,
            $this->uploadService->handleUploadRequest($request),
        );
    }

    public function testHandleUploadRequestWithCompleteResult(): void
    {
        $this->security->expects('getUser')->andReturn($user = \Mockery::mock(User::class));
        $this->security->expects('isGranted')->andReturnTrue();

        $request = new UploadRequest(
            2,
            3,
            $uploadId = 'foo-bar-123',
            \Mockery::mock(UploadedFile::class),
            $groupId = UploadGroupId::WOO_DECISION_DOCUMENTS,
            $params = new InputBag(),
        );

        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::INCOMPLETE);

        $this->uploadEntityRepository
            ->expects('findOrCreate')
            ->with($uploadId, $groupId, $user, $params)
            ->andReturn($uploadEntity);

        $this->uploadHandler
            ->expects('handleUploadRequest')
            ->with($uploadEntity, $request)
            ->andReturn($result = new UploadCompletedResult(
                uploadId: $uploadId,
                filename: $filename = 'foo.bar',
                groupId: $groupId,
                mimeType: 'application/pdf',
                size: $size = 123,
                additionalParameters: $params,
            ));

        $uploadEntity->expects('finishUploading')->with($filename, $size);

        $this->eventDispatcher->expects('dispatch')->with(\Mockery::on(
            static function (UploadCompletedEvent $event) use ($uploadEntity) {
                self::assertEquals($uploadEntity, $event->uploadEntity);

                return true;
            }
        ));

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        self::assertSame(
            $result,
            $this->uploadService->handleUploadRequest($request),
        );
    }

    public function testAbortUpload(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('abort');

        $this->uploadHandler->expects('deleteUploadedFile')->with($uploadEntity);

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        $this->uploadService->abortUpload($uploadEntity);
    }

    public function testDeleteUploadedFile(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $this->uploadHandler->expects('deleteUploadedFile')->with($uploadEntity);

        $this->uploadService->deleteUploadedFile($uploadEntity);
    }

    public function testCopyUploadToFilesystemThrowsExceptionWhenUploadIsNotDownloadable(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getUploadId')->andReturn('foo-123');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::ABORTED);

        $targetFilesystem = \Mockery::mock(FilesystemOperator::class);

        $this->expectException(UploadException::class);

        $this->uploadService->copyUploadToFilesystem($uploadEntity, $targetFilesystem, 'foo.bar');
    }

    public function testCopyUploadToFilesystemSuccessfully(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getUploadId')->andReturn('foo-123');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);

        $targetFilename = 'foo.bar';
        $targetFilesystem = \Mockery::mock(FilesystemOperator::class);
        $limit = 123;

        $this->uploadHandler->expects('copyUploadedFileToFilesystem')->with($uploadEntity, $limit, $targetFilesystem, $targetFilename);

        $this->uploadService->copyUploadToFilesystem($uploadEntity, $targetFilesystem, $targetFilename, $limit);
    }

    public function testMoveUploadToStorage(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('markAsStored');

        $targetFilename = 'foo.bar';
        $targetFilesystem = \Mockery::mock(FilesystemOperator::class);

        $this->uploadHandler
            ->expects('moveUploadedFileToStorage')
            ->with($uploadEntity, $targetFilesystem, $targetFilename);

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        $this->uploadService->moveUploadToStorage($uploadEntity, $targetFilesystem, $targetFilename);
    }

    public function testPassValidation(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('passValidation')->andReturn($mimetype = 'application/pdf');
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId = 'foo-123');

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        $this->workingCopyStorage->expects('delete')->with($uploadId);

        $this->eventDispatcher->expects('dispatch')->with(\Mockery::on(
            static function (UploadValidatedEvent $event) use ($uploadEntity) {
                self::assertEquals($uploadEntity, $event->uploadEntity);

                return true;
            }
        ));

        $this->uploadService->passValidation($uploadEntity, $mimetype);
    }

    public function testFailValidation(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn(UploadGroupId::WOO_DECISION_DOCUMENTS);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId = 'foo-123');

        $exception = UploadValidationException::forInvalidMimetype($uploadEntity, 'foo/bar');

        $uploadEntity->expects('failValidation')->with($exception);

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        $this->uploadHandler->expects('deleteUploadedFile')->with($uploadEntity);

        $this->workingCopyStorage->expects('delete')->with($uploadId);

        $this->uploadService->failValidation($uploadEntity, $exception);
    }
}
