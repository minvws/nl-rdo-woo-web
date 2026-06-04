<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

use League\Flysystem\FilesystemOperator;
use Mockery;
use Mockery\MockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\NullLogger;
use Shared\Domain\Upload\Event\UploadCompletedEvent;
use Shared\Domain\Upload\Event\UploadValidatedEvent;
use Shared\Domain\Upload\Exception\UploadException;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Domain\Upload\Handler\UploadHandlerInterface;
use Shared\Domain\Upload\Result\PartialUploadResult;
use Shared\Domain\Upload\Result\UploadCompletedResult;
use Shared\Domain\Upload\StreamUpload;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadEntityStatus;
use Shared\Domain\Upload\UploadRequest;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Security\User;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Uid\Uuid;

class UploadServiceTest extends UnitTestCase
{
    private UploadHandlerInterface&MockInterface $uploadHandler;
    private EventDispatcherInterface&MockInterface $eventDispatcher;
    private UploadEntityRepository&MockInterface $uploadEntityRepository;
    private FilesystemOperator&MockInterface $workingCopyStorage;
    private UploadService $uploadService;

    protected function setUp(): void
    {
        $this->uploadHandler = Mockery::mock(UploadHandlerInterface::class);
        $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $this->uploadEntityRepository = Mockery::mock(UploadEntityRepository::class);
        $this->workingCopyStorage = Mockery::mock(FilesystemOperator::class);

        $this->uploadService = new UploadService(
            $this->uploadHandler,
            $this->eventDispatcher,
            $this->uploadEntityRepository,
            $this->workingCopyStorage,
            new NullLogger(),
        );
    }

    public function testHandleUploadThrowsExceptionForAbortedUpload(): void
    {
        $user = Mockery::mock(User::class);
        $request = new UploadRequest(
            2,
            3,
            $uploadId = 'foo-bar-123',
            Mockery::mock(UploadedFile::class),
            $groupId = UploadGroupId::WOO_DECISION_DOCUMENTS,
            $params = new InputBag(),
        );

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getId')->andReturn(Uuid::v6());
        $uploadEntity->expects('getUploadId')->andReturn($uploadId);
        $uploadEntity->expects('getStatus')
            ->times(2)
            ->andReturn(UploadEntityStatus::ABORTED);

        $this->uploadEntityRepository
            ->expects('findOrCreate')
            ->with($uploadId, $groupId, $user, $params)
            ->andReturn($uploadEntity);

        $this->expectException(UploadException::class);
        $this->uploadService->handleUpload($request, $user);
    }

    public function testHandleUploadWithIncompleteResult(): void
    {
        $user = Mockery::mock(User::class);
        $request = new UploadRequest(
            2,
            3,
            $uploadId = 'foo-bar-123',
            Mockery::mock(UploadedFile::class),
            $groupId = UploadGroupId::WOO_DECISION_DOCUMENTS,
            $params = new InputBag(),
        );

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getStatus')->andReturn(UploadEntityStatus::INCOMPLETE);

        $this->uploadEntityRepository
        ->expects('findOrCreate')
        ->with($uploadId, $groupId, $user, $params)
        ->andReturn($uploadEntity);

        $this->uploadHandler
        ->expects('handleUpload')
        ->with($uploadEntity, $request)
        ->andReturn($result = new PartialUploadResult($uploadId, 'foo.bar', $groupId));

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        self::assertSame(
            $result,
            $this->uploadService->handleUpload($request, $user),
        );
    }

    public function testHandleUploadWithCompleteResult(): void
    {
        $user = Mockery::mock(User::class);
        $request = new UploadRequest(
            2,
            3,
            $uploadId = 'foo-bar-123',
            Mockery::mock(UploadedFile::class),
            $groupId = UploadGroupId::WOO_DECISION_DOCUMENTS,
            $params = new InputBag(),
        );

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getStatus')->andReturn(UploadEntityStatus::INCOMPLETE);

        $this->uploadEntityRepository
            ->expects('findOrCreate')
            ->with($uploadId, $groupId, $user, $params)
            ->andReturn($uploadEntity);

        $this->uploadHandler
            ->expects('handleUpload')
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

        $this->eventDispatcher->expects('dispatch')->with(Mockery::on(
            static function (UploadCompletedEvent $event) use ($uploadEntity) {
                self::assertEquals($uploadEntity, $event->uploadEntity);

                return true;
            },
        ));

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        self::assertSame(
            $result,
            $this->uploadService->handleUpload($request, $user),
        );
    }

    public function testHandleUploadPassingStreamUpload(): void
    {
        $user = null;
        $request = new StreamUpload(
            'foo.pdf',
            Mockery::mock(StreamInterface::class),
            $groupId = UploadGroupId::WOO_DECISION_DOCUMENTS,
            $params = new InputBag(['foo' => 'bar']),
            $uploadId = 'foo-bar-123',
        );

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getStatus')->andReturn(UploadEntityStatus::INCOMPLETE);

        $this->uploadEntityRepository
            ->expects('findOrCreate')
            ->with($uploadId, $groupId, $user, $params)
            ->andReturn($uploadEntity);

        $this->uploadHandler
            ->expects('handleStreamUpload')
            ->with($uploadEntity, $request)
            ->andReturn($result = new UploadCompletedResult(
                uploadId: $uploadId,
                filename: $filename = 'foo.bar',
                groupId: $groupId,
                mimeType: 'application/octet-stream',
                size: $size = 123,
                additionalParameters: $params,
            ));

        $uploadEntity->expects('finishUploading')->with($filename, $size);

        $this->eventDispatcher->expects('dispatch')->with(Mockery::on(
            static function (UploadCompletedEvent $event) use ($uploadEntity) {
                self::assertEquals($uploadEntity, $event->uploadEntity);

                return true;
            },
        ));

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        self::assertSame(
            $result,
            $this->uploadService->handleUpload($request, $user),
        );
    }

    public function testAbortUpload(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('abort');

        $this->uploadHandler->expects('deleteUploadedFile')->with($uploadEntity);

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        $this->uploadService->abortUpload($uploadEntity);
    }

    public function testDeleteUploadedFile(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $this->uploadHandler->expects('deleteUploadedFile')->with($uploadEntity);

        $this->uploadService->deleteUploadedFile($uploadEntity);
    }

    public function testCopyUploadToFilesystemThrowsExceptionWhenUploadIsNotDownloadable(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getId')->andReturn(Uuid::v6());
        $uploadEntity->expects('getUploadId')->andReturn('foo-123');
        $uploadEntity->expects('getStatus')
            ->times(2)
            ->andReturn(UploadEntityStatus::ABORTED);

        $targetFilesystem = Mockery::mock(FilesystemOperator::class);

        $this->expectException(UploadException::class);

        $this->uploadService->copyUploadToFilesystem($uploadEntity, $targetFilesystem, 'foo.bar');
    }

    public function testCopyUploadToFilesystemSuccessfully(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getStatus')
            ->andReturn(UploadEntityStatus::UPLOADED);

        $targetFilename = 'foo.bar';
        $targetFilesystem = Mockery::mock(FilesystemOperator::class);
        $limit = 123;

        $this->uploadHandler->expects('copyUploadedFileToFilesystem')->with($uploadEntity, $limit, $targetFilesystem, $targetFilename);

        $this->uploadService->copyUploadToFilesystem($uploadEntity, $targetFilesystem, $targetFilename, $limit);
    }

    public function testMoveUploadToStorage(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('markAsStored');

        $targetFilename = 'foo.bar';
        $targetFilesystem = Mockery::mock(FilesystemOperator::class);

        $this->uploadHandler
            ->expects('moveUploadedFileToStorage')
            ->with($uploadEntity, $targetFilesystem, $targetFilename);

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        $this->uploadService->moveUploadToStorage($uploadEntity, $targetFilesystem, $targetFilename);
    }

    public function testPassValidation(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getId')->andReturn(Uuid::v7());
        $uploadEntity->expects('passValidation')->andReturn($mimetype = 'application/pdf');
        $uploadEntity->expects('getUploadId')->andReturn($uploadId = 'foo-123');

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        $this->workingCopyStorage->expects('delete')->with($uploadId);

        $this->eventDispatcher->expects('dispatch')->with(Mockery::on(
            static function (UploadValidatedEvent $event) use ($uploadEntity) {
                self::assertEquals($uploadEntity, $event->uploadEntity);

                return true;
            },
        ));

        $this->uploadService->passValidation($uploadEntity, $mimetype);
    }

    public function testFailValidation(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getUploadGroupId')->andReturn(UploadGroupId::WOO_DECISION_DOCUMENTS);
        $uploadEntity->expects('getUploadId')->andReturn($uploadId = 'foo-123');

        $exception = UploadValidationException::forInvalidMimetype($uploadEntity, 'foo/bar');

        $uploadEntity->expects('failValidation')->with($exception);

        $this->uploadEntityRepository->expects('save')->with($uploadEntity, true);

        $this->uploadHandler->expects('deleteUploadedFile')->with($uploadEntity);

        $this->workingCopyStorage->expects('delete')->with($uploadId);

        $this->uploadService->failValidation($uploadEntity, $exception);
    }
}
