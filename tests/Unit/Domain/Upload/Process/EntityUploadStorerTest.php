<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Process;

use League\Flysystem\FilesystemOperator;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Upload\AssetsNamer;
use Shared\Domain\Upload\Process\EntityUploadStorer;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\Tests\Unit\UnitTestCase;

class EntityUploadStorerTest extends UnitTestCase
{
    private UploadService&MockInterface $uploadService;
    private FilesystemOperator&MockInterface $documentStorage;
    private FilesystemOperator&MockInterface $assetsStorage;
    private EntityStorageService&MockInterface $entityStorageService;
    private UploadEntityRepository&MockInterface $uploadEntityRepository;
    private EntityUploadStorer $uploadStorer;
    private AssetsNamer&MockInterface $assetsNamer;
    private ThumbnailStorageService&MockInterface $thumbnailStorageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadService = Mockery::mock(UploadService::class);
        $this->documentStorage = Mockery::mock(FilesystemOperator::class);
        $this->assetsStorage = Mockery::mock(FilesystemOperator::class);
        $this->entityStorageService = Mockery::mock(EntityStorageService::class);
        $this->uploadEntityRepository = Mockery::mock(UploadEntityRepository::class);
        $this->assetsNamer = Mockery::mock(AssetsNamer::class);
        $this->thumbnailStorageService = Mockery::mock(ThumbnailStorageService::class);

        $this->uploadStorer = new EntityUploadStorer(
            $this->uploadService,
            $this->documentStorage,
            $this->assetsStorage,
            $this->entityStorageService,
            $this->uploadEntityRepository,
            $this->assetsNamer,
            $this->thumbnailStorageService,
        );
    }

    public function testStoreUploadForEntity(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getFilename')->andReturn($filename = 'foo.bar');
        $uploadEntity->expects('getSize')->andReturn($size = 123);
        $uploadEntity->expects('getMimetype')->andReturn($mimetype = 'foo/bar');

        $targetEntity = Mockery::mock(Document::class);
        $targetEntity->expects('getFileInfo->isUploaded')->andReturnFalse();

        $this->entityStorageService
            ->expects('generateEntityPath')
            ->with($targetEntity, $filename)
            ->andReturn($path = '/some/path');

        $this->uploadService->expects('moveUploadToStorage')->with($uploadEntity, $this->documentStorage, $path);

        $targetEntity->expects('getFileInfo->setMimetype')->with($mimetype);
        $targetEntity->expects('getFileInfo->setSize')->with($size);
        $targetEntity->expects('getFileInfo->setPath')->with($path);
        $targetEntity->expects('getFileInfo->setUploaded')->with(true);
        $targetEntity->expects('getFileInfo->setPageCount')->with(null);

        $this->uploadStorer->storeUploadForEntity($uploadEntity, $targetEntity);
    }

    public function testStoreUploadForEntityDeletesThumbnailsWhenEntityAlreadyHasUpload(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getFilename')->andReturn($filename = 'foo.bar');
        $uploadEntity->expects('getSize')->andReturn($size = 123);
        $uploadEntity->expects('getMimetype')->andReturn($mimetype = 'foo/bar');

        $targetEntity = Mockery::mock(Document::class);
        $targetEntity->expects('getFileInfo->isUploaded')->andReturnTrue();

        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($targetEntity);

        $this->entityStorageService
            ->expects('generateEntityPath')
            ->with($targetEntity, $filename)
            ->andReturn($path = '/some/path');

        $this->uploadService->expects('moveUploadToStorage')->with($uploadEntity, $this->documentStorage, $path);

        $targetEntity->expects('getFileInfo->setMimetype')->with($mimetype);
        $targetEntity->expects('getFileInfo->setSize')->with($size);
        $targetEntity->expects('getFileInfo->setPath')->with($path);
        $targetEntity->expects('getFileInfo->setUploaded')->with(true);
        $targetEntity->expects('getFileInfo->setPageCount')->with(null);

        $this->uploadStorer->storeUploadForEntity($uploadEntity, $targetEntity);
    }

    public function testStoreUploadForEntityDoesNotDeleteThumbnailsForNewUpload(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getFilename')->andReturn($filename = 'foo.bar');
        $uploadEntity->expects('getSize')->andReturn($size = 123);
        $uploadEntity->expects('getMimetype')->andReturn($mimetype = 'foo/bar');

        $targetEntity = Mockery::mock(Document::class);
        $targetEntity->expects('getFileInfo->isUploaded')->andReturnFalse();

        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->never();

        $this->entityStorageService
            ->expects('generateEntityPath')
            ->with($targetEntity, $filename)
            ->andReturn($path = '/some/path');

        $this->uploadService->expects('moveUploadToStorage')->with($uploadEntity, $this->documentStorage, $path);

        $targetEntity->expects('getFileInfo->setMimetype')->with($mimetype);
        $targetEntity->expects('getFileInfo->setSize')->with($size);
        $targetEntity->expects('getFileInfo->setPath')->with($path);
        $targetEntity->expects('getFileInfo->setUploaded')->with(true);
        $targetEntity->expects('getFileInfo->setPageCount')->with(null);

        $this->uploadStorer->storeUploadForEntity($uploadEntity, $targetEntity);
    }

    public function testStoreUploadForEntityWithSourceTypeAndName(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getFilename')
            ->times(2)
            ->andReturn($filename = 'foo.bar');
        $uploadEntity->expects('getSize')->andReturn($size = 123);
        $uploadEntity->expects('getMimetype')
            ->times(2)
            ->andReturn($mimetype = 'application/pdf');

        $uploadId = 'foo-123';
        $this->uploadEntityRepository->expects('findOneBy')->with(['uploadId' => $uploadId])->andReturn($uploadEntity);

        $targetEntity = Mockery::mock(Document::class);
        $targetEntity->expects('getFileInfo->isUploaded')->andReturnFalse();

        $this->entityStorageService
            ->expects('generateEntityPath')
            ->with($targetEntity, $filename)
            ->andReturn($path = '/some/path');

        $this->uploadService->expects('moveUploadToStorage')->with($uploadEntity, $this->documentStorage, $path);

        $targetEntity->expects('getFileInfo->setMimetype')->with($mimetype);
        $targetEntity->expects('getFileInfo->setSize')->with($size);
        $targetEntity->expects('getFileInfo->setPath')->with($path);
        $targetEntity->expects('getFileInfo->setUploaded')->with(true);
        $targetEntity->expects('getFileInfo->setPageCount')->with(null);
        $targetEntity->expects('getFileInfo->setSourceType')->with(SourceType::PDF);
        $targetEntity->expects('getFileInfo->setName')->with($filename);

        $this->uploadStorer->storeUploadForEntityWithSourceTypeAndName($targetEntity, $uploadId);
    }

    public function testStoreDepartmentAssetForEntity(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getFilename')
            ->times(2)
            ->andReturn($filename = 'foo.svg');
        $uploadEntity->expects('getSize')->andReturn($size = 123);
        $uploadEntity->expects('getMimetype')->andReturn($mimetype = 'image/svg+xml');

        $targetEntity = Mockery::mock(Department::class);

        $this->assetsNamer
            ->expects('getDepartmentLogo')
            ->with($targetEntity, 'svg')
            ->andReturn($path = '/some/path/logo.svg');

        $this->uploadService
            ->expects('moveUploadToStorage')
            ->with($uploadEntity, $this->assetsStorage, $path);

        $targetEntity->expects('getFileInfo->setMimetype')->with($mimetype);
        $targetEntity->expects('getFileInfo->setSize')->with($size);
        $targetEntity->expects('getFileInfo->setPath')->with($path);
        $targetEntity->expects('getFileInfo->setUploaded')->with(true);
        $targetEntity->expects('getFileInfo->setPageCount')->with(null);
        $targetEntity->expects('getFileInfo->setName')->with($filename);

        $this->uploadStorer->storeDepartmentAssetForEntity($uploadEntity, $targetEntity);
    }
}
