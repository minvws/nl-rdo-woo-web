<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Process;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Upload\AssetsNamer;
use App\Domain\Upload\Process\EntityUploadStorer;
use App\Domain\Upload\UploadEntity;
use App\Domain\Upload\UploadEntityRepository;
use App\Domain\Upload\UploadService;
use App\Entity\Department;
use App\Service\Storage\EntityStorageService;
use App\Service\Uploader\UploadGroupId;
use App\SourceType;
use App\Tests\Unit\UnitTestCase;
use League\Flysystem\FilesystemOperator;
use Mockery\MockInterface;

class EntityUploadStorerTest extends UnitTestCase
{
    private UploadService&MockInterface $uploadService;
    private FilesystemOperator&MockInterface $documentStorage;
    private FilesystemOperator&MockInterface $assetsStorage;
    private EntityStorageService&MockInterface $entityStorageService;
    private UploadEntityRepository&MockInterface $uploadEntityRepository;
    private EntityUploadStorer $uploadStorer;
    private AssetsNamer&MockInterface $assetsNamer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadService = \Mockery::mock(UploadService::class);
        $this->documentStorage = \Mockery::mock(FilesystemOperator::class);
        $this->assetsStorage = \Mockery::mock(FilesystemOperator::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->uploadEntityRepository = \Mockery::mock(UploadEntityRepository::class);
        $this->assetsNamer = \Mockery::mock(AssetsNamer::class);

        $this->uploadStorer = new EntityUploadStorer(
            $this->uploadService,
            $this->documentStorage,
            $this->assetsStorage,
            $this->entityStorageService,
            $this->uploadEntityRepository,
            $this->assetsNamer,
        );
    }

    public function testStoreUploadForEntity(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn(UploadGroupId::WOO_DECISION_DOCUMENTS);
        $uploadEntity->shouldReceive('getFilename')->andReturn($filename = 'foo.bar');
        $uploadEntity->shouldReceive('getSize')->andReturn($size = 123);
        $uploadEntity->shouldReceive('getMimetype')->andReturn($mimetype = 'foo/bar');

        $targetEntity = \Mockery::mock(Document::class);

        $this->entityStorageService
            ->expects('generateEntityPath')
            ->with($targetEntity, $filename)
            ->andReturn($path = '/some/path');

        $this->uploadService->expects('moveUploadToStorage')->with($uploadEntity, $this->documentStorage, $path);

        $targetEntity->expects('getFileInfo->setMimetype')->with($mimetype);
        $targetEntity->expects('getFileInfo->setSize')->with($size);
        $targetEntity->expects('getFileInfo->setPath')->with($path);
        $targetEntity->expects('getFileInfo->setUploaded')->with(true);

        $this->uploadStorer->storeUploadForEntity($uploadEntity, $targetEntity);
    }

    public function testStoreUploadForEntityWithSourceTypeAndName(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn(UploadGroupId::WOO_DECISION_DOCUMENTS);
        $uploadEntity->shouldReceive('getFilename')->andReturn($filename = 'foo.bar');
        $uploadEntity->shouldReceive('getSize')->andReturn($size = 123);
        $uploadEntity->shouldReceive('getMimetype')->andReturn($mimetype = 'application/pdf');

        $uploadId = 'foo-123';
        $this->uploadEntityRepository->expects('findOneBy')->with(['uploadId' => $uploadId])->andReturn($uploadEntity);

        $targetEntity = \Mockery::mock(Document::class);

        $this->entityStorageService
            ->expects('generateEntityPath')
            ->with($targetEntity, $filename)
            ->andReturn($path = '/some/path');

        $this->uploadService->expects('moveUploadToStorage')->with($uploadEntity, $this->documentStorage, $path);

        $targetEntity->expects('getFileInfo->setMimetype')->with($mimetype);
        $targetEntity->expects('getFileInfo->setSize')->with($size);
        $targetEntity->expects('getFileInfo->setPath')->with($path);
        $targetEntity->expects('getFileInfo->setUploaded')->with(true);
        $targetEntity->expects('getFileInfo->setSourceType')->with(SourceType::PDF);
        $targetEntity->expects('getFileInfo->setName')->with($filename);

        $this->uploadStorer->storeUploadForEntityWithSourceTypeAndName($targetEntity, $uploadId);
    }

    public function testStoreDepartmentAssetForEntity(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn(UploadGroupId::WOO_DECISION_DOCUMENTS);
        $uploadEntity->shouldReceive('getFilename')->andReturn($filename = 'foo.svg');
        $uploadEntity->shouldReceive('getSize')->andReturn($size = 123);
        $uploadEntity->shouldReceive('getMimetype')->andReturn($mimetype = 'image/svg+xml');

        $targetEntity = \Mockery::mock(Department::class);

        $this->assetsNamer
            ->shouldReceive('getDepartmentLogo')
            ->with($targetEntity, 'svg')
            ->andReturn($path = '/some/path/logo.svg');

        $this->uploadService
            ->shouldReceive('moveUploadToStorage')
            ->with($uploadEntity, $this->assetsStorage, $path);

        $targetEntity->expects('getFileInfo->setMimetype')->with($mimetype);
        $targetEntity->expects('getFileInfo->setSize')->with($size);
        $targetEntity->expects('getFileInfo->setPath')->with($path);
        $targetEntity->expects('getFileInfo->setUploaded')->with(true);
        $targetEntity->expects('getFileInfo->setName')->with($filename);

        $this->uploadStorer->storeDepartmentAssetForEntity($uploadEntity, $targetEntity);
    }
}
