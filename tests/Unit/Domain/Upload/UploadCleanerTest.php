<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

use Carbon\CarbonImmutable;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Mockery\MockInterface;
use Shared\Domain\Upload\UploadCleaner;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadService;
use Shared\Tests\Unit\UnitTestCase;

class UploadCleanerTest extends UnitTestCase
{
    private UploadEntityRepository&MockInterface $uploadEntityRepository;
    private FilesystemOperator&MockInterface $uploadStorage;
    private FilesystemOperator&MockInterface $workingCopyStorage;
    private UploadService&MockInterface $uploadService;
    private UploadCleaner $uploadCleaner;

    protected function setUp(): void
    {
        $this->uploadEntityRepository = \Mockery::mock(UploadEntityRepository::class);
        $this->uploadService = \Mockery::mock(UploadService::class);
        $this->uploadStorage = \Mockery::mock(FilesystemOperator::class);
        $this->workingCopyStorage = \Mockery::mock(FilesystemOperator::class);

        $this->uploadCleaner = new UploadCleaner(
            $this->uploadEntityRepository,
            $this->workingCopyStorage,
            $this->uploadStorage,
            $this->uploadService,
        );
    }

    public function testCleanup(): void
    {
        $uploadA = \Mockery::mock(UploadEntity::class);
        $uploadB = \Mockery::mock(UploadEntity::class);

        $this->uploadEntityRepository->expects('findUploadsForCleanup')->andReturn([$uploadA, $uploadB]);

        $this->uploadService->expects('deleteUploadedFile')->with($uploadA);
        $this->uploadEntityRepository->expects('remove')->with($uploadA, true);

        $this->uploadService->expects('deleteUploadedFile')->with($uploadB)->andThrows(new \RuntimeException('Oops'));
        $this->uploadEntityRepository->expects('remove')->with($uploadB, true);

        $directory = \Mockery::mock(StorageAttributes::class);
        $directory->shouldReceive('isFile')->andReturnFalse();

        $oldFile = \Mockery::mock(StorageAttributes::class);
        $oldFile->shouldReceive('isFile')->andReturnTrue();
        $oldFile->shouldReceive('lastModified')->andReturn(CarbonImmutable::now()->subMonths(2)->timestamp);
        $oldFile->shouldReceive('path')->andReturn($oldFilePath = 'foo.bar');

        $recentFile = \Mockery::mock(StorageAttributes::class);
        $recentFile->shouldReceive('isFile')->andReturnTrue();
        $recentFile->shouldReceive('lastModified')->andReturn(CarbonImmutable::now()->subDay()->timestamp);

        $this->uploadStorage
            ->expects('listContents')
            ->andReturn(new DirectoryListing([$directory, $oldFile, $recentFile]));
        $this->uploadStorage->expects('delete')->with($oldFilePath);

        $this->workingCopyStorage
            ->expects('listContents')
            ->andReturn(new DirectoryListing([$directory, $oldFile, $recentFile]));
        $this->workingCopyStorage->expects('delete')->with($oldFilePath);

        $this->uploadCleaner->cleanup();
    }
}
