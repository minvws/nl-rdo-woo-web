<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Storage;

use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Publication\FileInfo;
use App\Service\Storage\LocalFilesystem;
use App\Service\Storage\RemoteFilesystem;
use App\Service\Storage\StorageRootPathGenerator;
use App\Service\Storage\ThumbnailStorageService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

final class ThumbnailStorageServiceTest extends UnitTestCase
{
    private RemoteFilesystem&MockInterface $remoteFilesystem;
    private LocalFilesystem&MockInterface $localFilesystem;
    private LoggerInterface&MockInterface $logger;
    private StorageRootPathGenerator&MockInterface $rootPathGenerator;
    private int $thumbnailLimit = 13;

    protected function setUp(): void
    {
        parent::setUp();

        $this->remoteFilesystem = \Mockery::mock(RemoteFilesystem::class);
        $this->localFilesystem = \Mockery::mock(LocalFilesystem::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->rootPathGenerator = \Mockery::mock(StorageRootPathGenerator::class);
    }

    public function testRetrieveResource(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);

        $this->rootPathGenerator
            ->shouldReceive('__invoke')
            ->once()
            ->with($entity)
            ->andReturn($rootPath = 'root-path');

        $remotePath = sprintf('%s/thumbs/thumb-page-%d.png', $rootPath, $pageNr = 1);

        $this->remoteFilesystem
            ->shouldReceive('readStream')
            ->once()
            ->with($remotePath)
            ->andReturn($stream = $this->getTempResource());

        $result = $this->getStorageService()->retrieveResource($entity, $pageNr);

        $this->assertIsResource($result);
        $this->assertSame($stream, $result);
    }

    public function testStore(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $localFile = \Mockery::mock(\SplFileInfo::class);

        $service = $this->getStorageService();
        $service
            ->shouldReceive('generateThumbPath')
            ->once()
            ->with($entity, $pageNr = 1)
            ->andReturn($remotePath = 'remote-path');

        $this->remoteFilesystem
            ->shouldReceive('createDirectoryIfNotExist')
            ->once()
            ->with(dirname($remotePath))
            ->andReturnTrue();

        $service
            ->shouldReceive('doStore')
            ->once()
            ->with($localFile, $remotePath)
            ->andReturnTrue();

        $result = $service->store($entity, $localFile, $pageNr);

        $this->assertTrue($result);
    }

    public function testStoreWhenCreatingDirectoryFails(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $localFile = \Mockery::mock(\SplFileInfo::class);

        $service = $this->getStorageService();
        $service
            ->shouldReceive('generateThumbPath')
            ->once()
            ->with($entity, $pageNr = 1)
            ->andReturn($remotePath = 'remote-path');

        $this->remoteFilesystem
            ->shouldReceive('createDirectoryIfNotExist')
            ->once()
            ->with(dirname($remotePath))
            ->andReturnFalse();

        $this->remoteFilesystem->shouldNotReceive('createDirectory');

        $service->shouldNotReceive('doStore');

        $result = $service->store($entity, $localFile, $pageNr);

        $this->assertFalse($result);
    }

    public function testExists(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);

        $service = $this->getStorageService();
        $service->shouldReceive('generateThumbPath')
            ->once()
            ->with($entity, $pageNr = 1)
            ->andReturn($remotePath = 'remote-path');

        $this->remoteFilesystem
            ->shouldReceive('fileExists')
            ->once()
            ->with($remotePath)
            ->andReturnTrue();

        $result = $service->exists($entity, $pageNr);

        $this->assertTrue($result);
    }

    public function testFileSize(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);

        $service = $this->getStorageService();
        $service->shouldReceive('generateThumbPath')
            ->once()
            ->with($entity, $pageNr = 1)
            ->andReturn($path = 'path');

        $this->remoteFilesystem
            ->shouldReceive('fileSize')
            ->once()
            ->with($path)
            ->andReturn($size = 123);

        $result = $service->fileSize($entity, $pageNr);

        $this->assertSame($size, $result);
    }

    public function testDeleteAllThumbsForEntity(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('hasPages')->andReturnTrue();
        $fileInfo
            ->shouldReceive('isPaginatable')
            ->once()
            ->andReturnTrue();
        $fileInfo
            ->shouldReceive('getPageCount')
            ->once()
            ->andReturn(3);

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')
            ->andReturn($fileInfo);

        $service = $this->getStorageService();

        $service
            ->shouldReceive('generateThumbPath')
            ->once()
            ->with($entity, 1)
            ->andReturn('remote-path/1');
        $this->remoteFilesystem
            ->shouldReceive('delete')
            ->once()
            ->with('remote-path/1')
            ->andReturnTrue();

        $service
            ->shouldReceive('generateThumbPath')
            ->once()
            ->with($entity, 2)
            ->andReturn('remote-path/2');
        $this->remoteFilesystem
            ->shouldReceive('delete')
            ->once()
            ->with('remote-path/2')
            ->andReturnTrue();

        $service
            ->shouldReceive('generateThumbPath')
            ->once()
            ->with($entity, 3)
            ->andReturn('remote-path/3');
        $this->remoteFilesystem
            ->shouldReceive('delete')
            ->once()
            ->with('remote-path/3')
            ->andReturnTrue();

        $result = $service->deleteAllThumbsForEntity($entity);

        $this->assertTrue($result);
    }

    private function getStorageService(): ThumbnailStorageService&MockInterface
    {
        /** @var ThumbnailStorageService&MockInterface $service */
        $service = \Mockery::mock(ThumbnailStorageService::class, [
            $this->remoteFilesystem,
            $this->localFilesystem,
            $this->logger,
            $this->rootPathGenerator,
            $this->thumbnailLimit,
        ])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        return $service;
    }

    /**
     * @return resource
     */
    private function getTempResource()
    {
        $result = fopen('php://memory', 'r');

        Assert::resource($result, 'stream', 'The resource should be a stream');

        return $result;
    }
}
