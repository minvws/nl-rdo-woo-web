<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Storage;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Service\Storage\LocalFilesystem;
use Shared\Service\Storage\RemoteFilesystem;
use Shared\Service\Storage\StorageRootPathGenerator;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\Tests\Unit\UnitTestCase;
use SplFileInfo;
use Webmozart\Assert\Assert;

use function dirname;
use function fopen;
use function sprintf;

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

        $this->remoteFilesystem = Mockery::mock(RemoteFilesystem::class);
        $this->localFilesystem = Mockery::mock(LocalFilesystem::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->rootPathGenerator = Mockery::mock(StorageRootPathGenerator::class);
    }

    public function testRetrieveResource(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);

        $this->rootPathGenerator
            ->expects('__invoke')
            ->with($entity)
            ->andReturn($rootPath = 'root-path');

        $remotePath = sprintf('%s/thumbs/thumb-page-%d.png', $rootPath, $pageNr = 1);

        $this->remoteFilesystem
            ->expects('readStream')
            ->with($remotePath)
            ->andReturn($stream = $this->getTempResource());

        $result = $this->getStorageService()->retrieveResource($entity, $pageNr);

        $this->assertIsResource($result);
        $this->assertSame($stream, $result);
    }

    public function testStore(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $localFile = Mockery::mock(SplFileInfo::class);

        $service = $this->getStorageService();
        $service
            ->expects('generateThumbPath')
            ->with($entity, $pageNr = 1)
            ->andReturn($remotePath = 'remote-path');

        $this->remoteFilesystem
            ->expects('createDirectoryIfNotExist')
            ->with(dirname($remotePath))
            ->andReturnTrue();

        $service
            ->expects('doStore')
            ->with($localFile, $remotePath)
            ->andReturnTrue();

        $result = $service->store($entity, $localFile, $pageNr);

        $this->assertTrue($result);
    }

    public function testStoreWhenCreatingDirectoryFails(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $localFile = Mockery::mock(SplFileInfo::class);

        $service = $this->getStorageService();
        $service
            ->expects('generateThumbPath')
            ->with($entity, $pageNr = 1)
            ->andReturn($remotePath = 'remote-path');

        $this->remoteFilesystem
            ->expects('createDirectoryIfNotExist')
            ->with(dirname($remotePath))
            ->andReturnFalse();

        $this->remoteFilesystem->shouldNotReceive('createDirectory');

        $service->shouldNotReceive('doStore');

        $result = $service->store($entity, $localFile, $pageNr);

        $this->assertFalse($result);
    }

    public function testExists(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);

        $service = $this->getStorageService();
        $service->expects('generateThumbPath')
            ->with($entity, $pageNr = 1)
            ->andReturn($remotePath = 'remote-path');

        $this->remoteFilesystem
            ->expects('fileExists')
            ->with($remotePath)
            ->andReturnTrue();

        $result = $service->exists($entity, $pageNr);

        $this->assertTrue($result);
    }

    public function testFileSize(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);

        $service = $this->getStorageService();
        $service->expects('generateThumbPath')
            ->with($entity, $pageNr = 1)
            ->andReturn($path = 'path');

        $this->remoteFilesystem
            ->expects('fileSize')
            ->with($path)
            ->andReturn($size = 123);

        $result = $service->fileSize($entity, $pageNr);

        $this->assertSame($size, $result);
    }

    public function testDeleteAllThumbsForEntity(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('hasPages')->andReturnTrue();
        $fileInfo
            ->expects('isPaginatable')
            ->andReturnTrue();
        $fileInfo
            ->expects('getPageCount')
            ->andReturn(3);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')
            ->times(3)
            ->andReturn($fileInfo);

        $service = $this->getStorageService();

        $service
            ->expects('generateThumbPath')
            ->with($entity, 1)
            ->andReturn('remote-path/1');
        $this->remoteFilesystem
            ->expects('delete')
            ->with('remote-path/1')
            ->andReturnTrue();

        $service
            ->expects('generateThumbPath')
            ->with($entity, 2)
            ->andReturn('remote-path/2');
        $this->remoteFilesystem
            ->expects('delete')
            ->with('remote-path/2')
            ->andReturnTrue();

        $service
            ->expects('generateThumbPath')
            ->with($entity, 3)
            ->andReturn('remote-path/3');
        $this->remoteFilesystem
            ->expects('delete')
            ->with('remote-path/3')
            ->andReturnTrue();

        $service->deleteAllThumbsForEntity($entity);
    }

    public function testDeleteAllThumbsForEntitySkipsWhenEntityHasNoPages(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('hasPages')->andReturnFalse();
        $fileInfo->shouldNotReceive('getPageCount');

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $service = $this->getStorageService();
        $service->shouldNotReceive('generateThumbPath');

        $service->deleteAllThumbsForEntity($entity);
    }

    private function getStorageService(): ThumbnailStorageService&MockInterface
    {
        return Mockery::mock(ThumbnailStorageService::class, [
            $this->remoteFilesystem,
            $this->localFilesystem,
            $this->logger,
            $this->rootPathGenerator,
            $this->thumbnailLimit,
        ])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
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
