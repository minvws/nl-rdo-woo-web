<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Command;

use App\Domain\Upload\AntiVirus\ClamAvFileScanner;
use App\Domain\Upload\AntiVirus\FileScanResult;
use App\Domain\Upload\Command\ValidateUploadCommand;
use App\Domain\Upload\Command\ValidateUploadCommandHandler;
use App\Domain\Upload\Exception\UploadException;
use App\Domain\Upload\Exception\UploadValidationException;
use App\Domain\Upload\FileType\MimeTypeHelper;
use App\Domain\Upload\Preprocessor\Strategy\SevenZipFileStrategy;
use App\Domain\Upload\UploadEntity;
use App\Domain\Upload\UploadEntityRepository;
use App\Domain\Upload\UploadService;
use App\Domain\Upload\UploadStatus;
use App\Service\Uploader\UploadGroupId;
use League\Flysystem\FilesystemOperator;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class ValidateUploadCommandHandlerTest extends MockeryTestCase
{
    private UploadEntityRepository&MockInterface $uploadEntityRepository;
    private UploadService&MockInterface $uploadService;
    private FilesystemOperator&MockInterface $workingCopyStorage;
    private ClamAvFileScanner&MockInterface $clamAvFileScanner;
    private MimeTypeHelper&MockInterface $mimeTypeHelper;
    private SevenZipFileStrategy&MockInterface $sevenZipFileStrategy;
    private ValidateUploadCommandHandler $handler;

    public function setUp(): void
    {
        $this->uploadEntityRepository = \Mockery::mock(UploadEntityRepository::class);
        $this->uploadService = \Mockery::mock(UploadService::class);
        $this->workingCopyStorage = \Mockery::mock(FilesystemOperator::class);
        $this->clamAvFileScanner = \Mockery::mock(ClamAvFileScanner::class);
        $this->mimeTypeHelper = \Mockery::mock(MimeTypeHelper::class);
        $this->sevenZipFileStrategy = \Mockery::mock(SevenZipFileStrategy::class);

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
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(123);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn('foo.bar');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(1024);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, null);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn('file data content');

        $this->uploadService
            ->expects('failValidation')
            ->with($uploadEntity, \Mockery::type(UploadValidationException::class));

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }

    public function testIncompleteUploadFailsValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = \Mockery::mock(UploadEntity::class);
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

    public function testTooLargePdfFailsValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(2048);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn('foo.pdf');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn($groupId = UploadGroupId::WOO_DECISION_DOCUMENTS);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(1024);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, $this->mimeTypeHelper::SAMPLE_SIZE);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn('file data content');

        $this->mimeTypeHelper->expects('isValidForUploadGroup')->with('application/pdf', $groupId)->andReturnFalse();

        $this->uploadService
            ->expects('failValidation')
            ->with($uploadEntity, \Mockery::type(UploadValidationException::class));

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }

    public function testTooLargeZipPassesValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(2048);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn('foo.zip');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn($groupId = UploadGroupId::WOO_DECISION_DOCUMENTS);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(1024);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, $this->mimeTypeHelper::SAMPLE_SIZE);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn('file data content');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with($mimeType = 'application/zip', $groupId)
            ->andReturnTrue();

        $this->sevenZipFileStrategy->expects('supports')->with('zip', $mimeType)->andReturnTrue();

        $this->uploadService->expects('passValidation')->with($uploadEntity, $mimeType);

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }

    public function testMimetypeMismatchFailsValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(124);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn('foo.pdf');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn($groupId = UploadGroupId::WOO_DECISION_DOCUMENTS);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(1024);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, null);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn('file data content');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('application/pdf', $groupId)
            ->andReturnFalse();

        $this->uploadService
            ->expects('failValidation')
            ->with($uploadEntity, \Mockery::type(UploadValidationException::class));

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }

    public function testUnsafeFileFailsValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(124);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn('foo.pdf');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn($groupId = UploadGroupId::WOO_DECISION_DOCUMENTS);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(1024);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, null);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn('file data content');
        $this->workingCopyStorage->expects('readStream')->with($uploadId)->andReturn($data = 'file data stream');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('application/pdf', $groupId)
            ->andReturnTrue();

        $this->clamAvFileScanner->expects('scanResource')->with($uploadId, $data)->andReturn(FileScanResult::UNSAFE);

        $this->uploadService
            ->expects('failValidation')
            ->with($uploadEntity, \Mockery::type(UploadValidationException::class));

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }

    public function testSafeFilePassesValidation(): void
    {
        $uuid = Uuid::v6();
        $uploadId = 'foo-123';
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getId')->andReturn(Uuid::v6());
        $uploadEntity->shouldReceive('getSize')->andReturn(124);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId);
        $uploadEntity->shouldReceive('getFilename')->andReturn('foo.pdf');
        $uploadEntity->shouldReceive('getStatus')->andReturn(UploadStatus::UPLOADED);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn($groupId = UploadGroupId::WOO_DECISION_DOCUMENTS);

        $this->clamAvFileScanner->expects('getFileSizeLimit')->andReturn(1024);

        $this->uploadEntityRepository->expects('find')->with($uuid)->andReturn($uploadEntity);

        $this->uploadService
            ->expects('copyUploadToFilesystem')
            ->with($uploadEntity, $this->workingCopyStorage, $uploadId, null);

        $this->workingCopyStorage->expects('read')->with($uploadId)->andReturn('file data content');
        $this->workingCopyStorage->expects('readStream')->with($uploadId)->andReturn($data = 'file data stream');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with($mimeType = 'application/pdf', $groupId)
            ->andReturnTrue();

        $this->clamAvFileScanner->expects('scanResource')->with($uploadId, $data)->andReturn(FileScanResult::SAFE);

        $this->uploadService->expects('passValidation')->with($uploadEntity, $mimeType);

        $command = new ValidateUploadCommand($uuid);

        $this->handler->__invoke($command);
    }
}
