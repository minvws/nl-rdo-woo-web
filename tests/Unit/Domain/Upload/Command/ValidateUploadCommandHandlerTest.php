<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Command;

use League\Flysystem\FilesystemOperator;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Upload\AntiVirus\ClamAvFileScanner;
use Shared\Domain\Upload\AntiVirus\FileScanResult;
use Shared\Domain\Upload\Command\ValidateUploadCommand;
use Shared\Domain\Upload\Command\ValidateUploadCommandHandler;
use Shared\Domain\Upload\Exception\UploadException;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Domain\Upload\FileType\FileType;
use Shared\Domain\Upload\FileType\MimeTypeHelper;
use Shared\Domain\Upload\Preprocessor\Strategy\SevenZipFileStrategy;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadService;
use Shared\Domain\Upload\UploadStatus;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class ValidateUploadCommandHandlerTest extends UnitTestCase
{
    private UploadEntityRepository&MockInterface $uploadEntityRepository;
    private UploadService&MockInterface $uploadService;
    private FilesystemOperator&MockInterface $workingCopyStorage;
    private ClamAvFileScanner&MockInterface $clamAvFileScanner;
    private MimeTypeHelper&MockInterface $mimeTypeHelper;
    private SevenZipFileStrategy&MockInterface $sevenZipFileStrategy;
    private ValidateUploadCommandHandler $handler;

    protected function setUp(): void
    {
        $this->uploadEntityRepository = Mockery::mock(UploadEntityRepository::class);
        $this->uploadService = Mockery::mock(UploadService::class);
        $this->workingCopyStorage = Mockery::mock(FilesystemOperator::class);
        $this->clamAvFileScanner = Mockery::mock(ClamAvFileScanner::class);
        $this->mimeTypeHelper = Mockery::mock(MimeTypeHelper::class);
        $this->sevenZipFileStrategy = Mockery::mock(SevenZipFileStrategy::class);

        $this->handler = new ValidateUploadCommandHandler(
            $this->uploadEntityRepository,
            $this->uploadService,
            $this->workingCopyStorage,
            $this->clamAvFileScanner,
            $this->mimeTypeHelper,
            $this->sevenZipFileStrategy,
        );
    }

    public function testInvalidMimetypeFailsValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(123);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn($filename = 'foo.bar');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(1024);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, null);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn($contents = 'file data content');

        $this->mimeTypeHelper
            ->shouldReceive('detectMimeType')
            ->with($filename, $contents)
            ->andReturnNull();

        $this->uploadService
            ->expects('failValidation')
            ->with($uploadEntity, Mockery::type(UploadValidationException::class));

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }

    public function testIncompleteUploadFailsValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(123);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn('foo.bar');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::INCOMPLETE);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $command = new ValidateUploadCommand($uuid);

        $this->expectException(UploadException::class);
        $this->handler->__invoke($command);
    }

    public function testTooLargePdfFailsMaxUploadSizeValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(FileType::PDF->getMaxUploadSize() + 1);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn($filename = 'foo.pdf');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn($groupId = UploadGroupId::WOO_DECISION_DOCUMENTS);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(10 * 1024 * 1024 * 1024); // 10 GiB

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, null);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn($contents = 'file data content');

        $this->mimeTypeHelper
            ->shouldReceive('detectMimeType')
            ->with($filename, $contents)
            ->andReturn($mimeType = 'application/pdf');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('pdf', $mimeType, $groupId)
            ->andReturnTrue();

        $expectedExceptionMessage = UploadValidationException::forFilesizeExceeded($uploadEntity, FileType::PDF)
            ->getMessage();

        $this->uploadService
            ->expects('failValidation')
            ->with(
                $uploadEntity,
                Mockery::on(fn (UploadValidationException $exception): bool => $exception->getMessage() === $expectedExceptionMessage),
            );

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }

    public function testTooLargeNonZipFileFailsClamAvFileSizeValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(2048);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn($filename = 'foo.pdf');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn($groupId = UploadGroupId::WOO_DECISION_DOCUMENTS);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(1024);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, $this->mimeTypeHelper::SAMPLE_SIZE);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn($contents = 'file data content');
        $this->workingCopyStorage->expects('readStream')->with($uploadId)->andReturn($data = 'file data stream');

        $this->mimeTypeHelper
            ->shouldReceive('detectMimeType')
            ->with($filename, $contents)
            ->andReturn($mimeType = 'application/pdf');

        $this->mimeTypeHelper->expects('isValidForUploadGroup')->with('pdf', $mimeType, $groupId)->andReturnTrue();

        $this->sevenZipFileStrategy->expects('supports')->with('pdf', $mimeType)->andReturnfalse();

        $this->clamAvFileScanner->expects('scanResource')->with($uploadId, $data)->andReturn(FileScanResult::MAX_SIZE_EXCEEDED);

        $expectedExceptionMessage = UploadValidationException::forUnsafeFile()->getMessage();
        $this->uploadService
            ->expects('failValidation')
            ->with(
                $uploadEntity,
                Mockery::on(fn (UploadValidationException $exception): bool => $exception->getMessage() === $expectedExceptionMessage),
            );

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }

    public function testTooLargeZipPassesValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(2048);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn($filename = 'foo.zip');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn($groupId = UploadGroupId::WOO_DECISION_DOCUMENTS);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(1024);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, $this->mimeTypeHelper::SAMPLE_SIZE);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn($contents = 'file data content');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('zip', $mimeType = 'application/zip', $groupId)
            ->andReturnTrue();

        $this->mimeTypeHelper
            ->shouldReceive('detectMimeType')
            ->with($filename, $contents)
            ->andReturn($mimeType);

        $this->sevenZipFileStrategy->expects('supports')->with('zip', $mimeType)->andReturnTrue();

        $this->uploadService->expects('passValidation')->with($uploadEntity, $mimeType);

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }

    public function testMimetypeMismatchFailsValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(124);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn($filename = 'foo.pdf');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn($groupId = UploadGroupId::WOO_DECISION_DOCUMENTS);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(1024);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, null);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn($contents = 'file data content');

        $this->mimeTypeHelper
            ->shouldReceive('detectMimeType')
            ->with($filename, $contents)
            ->andReturn($mimeType = 'application/pdf');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('pdf', $mimeType, $groupId)
            ->andReturnFalse();

        $this->uploadService
            ->expects('failValidation')
            ->with($uploadEntity, Mockery::type(UploadValidationException::class));

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }

    public function testUnsafeFileFailsValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(124);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn($filename = 'foo.pdf');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn($groupId = UploadGroupId::WOO_DECISION_DOCUMENTS);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(1024);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, null);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn($contents = 'file data content');
        $this->workingCopyStorage->expects('readStream')->with($uploadId)->andReturn($data = 'file data stream');

        $this->mimeTypeHelper
            ->shouldReceive('detectMimeType')
            ->with($filename, $contents)
            ->andReturn($mimeType = 'application/pdf');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('pdf', $mimeType, $groupId)
            ->andReturnTrue();

        $this->clamAvFileScanner->expects('scanResource')->with($uploadId, $data)->andReturn(FileScanResult::UNSAFE);

        $this->uploadService
            ->expects('failValidation')
            ->with($uploadEntity, Mockery::type(UploadValidationException::class));

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }

    public function testSafeFilePassesValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(124);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn($filename = 'foo.pdf');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn($groupId = UploadGroupId::WOO_DECISION_DOCUMENTS);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(1024);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, null);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn($contents = 'file data content');
        $this->workingCopyStorage->expects('readStream')->with($uploadId)->andReturn($data = 'file data stream');

        $this->mimeTypeHelper
            ->shouldReceive('detectMimeType')
            ->with($filename, $contents)
            ->andReturn($mimeType = 'application/pdf');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('pdf', $mimeType, $groupId)
            ->andReturnTrue();

        $this->clamAvFileScanner->expects('scanResource')->with($uploadId, $data)->andReturn(FileScanResult::SAFE);

        $this->uploadService->expects('passValidation')->with($uploadEntity, $mimeType);

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }
}
