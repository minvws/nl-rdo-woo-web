<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Storage;

use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Ingest\Content\Event\EntityFileUpdateEvent;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\LocalFilesystem;
use Shared\Service\Storage\RemoteFilesystem;
use Shared\Service\Storage\StorageRootPathGenerator;
use Shared\Tests\Unit\UnitTestCase;
use SplFileInfo;
use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

use function fopen;
use function sprintf;

class EntityStorageServiceTest extends UnitTestCase
{
    private RemoteFilesystem&MockInterface $remoteFilesystem;
    private LocalFilesystem&MockInterface $localFilesystem;
    private LoggerInterface&MockInterface $logger;
    private StorageRootPathGenerator&MockInterface $rootPathGenerator;
    private MessageBusInterface&MockInterface $messageBus;
    private EntityManagerInterface&MockInterface $doctrine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->remoteFilesystem = Mockery::mock(RemoteFilesystem::class);
        $this->localFilesystem = Mockery::mock(LocalFilesystem::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->rootPathGenerator = Mockery::mock(StorageRootPathGenerator::class);
        $this->messageBus = Mockery::mock(MessageBusInterface::class);
        $this->doctrine = Mockery::mock(EntityManagerInterface::class);
    }

    public function testStore(): void
    {
        $splFileInfo = Mockery::mock(SplFileInfo::class);
        $splFileInfo->expects('getPathname')->andReturn($pathName = 'pathName');

        $this->localFilesystem
            ->expects('createStream')
            ->with($pathName, 'r')
            ->andReturn($stream = $this->getTempResource());

        $this->remoteFilesystem
            ->expects('writeStream')
            ->with($remotePath = 'remotePath', $stream)
            ->andReturnTrue();

        $result = $this->getStorageService()->store($splFileInfo, $remotePath);

        $this->assertTrue($result);
    }

    public function testFailedStore(): void
    {
        $splFileInfo = Mockery::mock(SplFileInfo::class);
        $splFileInfo->expects('getPathname')->andReturn($pathName = 'pathName');

        $this->localFilesystem
            ->expects('createStream')
            ->with($pathName, 'r')
            ->andReturnFalse();

        $this->remoteFilesystem->shouldNotReceive('writeStream');

        $result = $this->getStorageService()->store($splFileInfo, 'remotePath');

        $this->assertFalse($result);
    }

    public function testRetrieveResourceEntity(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getPath')->andReturn('aPath/myFilename.pdf');

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $this->rootPathGenerator
            ->expects('__invoke')
            ->with($entity)
            ->andReturn('rootPath');

        $remotePath = 'rootPath/myFilename.pdf';

        $this->remoteFilesystem
            ->expects('readStream')
            ->with($remotePath)
            ->andReturn($stream = $this->getTempResource());

        $result = $this->getStorageService()->retrieveResourceEntity($entity);

        $this->assertIsResource($stream);
        $this->assertSame($stream, $result);
    }

    public function testStoreEntity(): void
    {
        $root = vfsStream::setup();
        vfsStream::create(['someDir' => ['clientName.pdf' => '']], $root);

        $splFileInfo = Mockery::mock(UploadedFile::class);
        $splFileInfo->expects('getClientOriginalName')->andReturn('clientName.pdf');
        $splFileInfo->expects('getPathname')->times(3)->andReturn($pathName = 'vfs://root/someDir/clientName.pdf');
        $splFileInfo->expects('getSize')->andReturn($size = 123);

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('setPath')->with($remotePath = 'vfs://root/remotePath/clientName.pdf');
        $fileInfo->expects('setSize')->with($size);
        $fileInfo->expects('setMimetype')->with('application/x-empty');
        $fileInfo->expects('getHash')->andReturn('old-hash');
        $fileInfo->expects('setHash')->with('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855');
        $fileInfo->expects('setUploaded')->with(true);
        $fileInfo->expects('setPageCount')->with(null);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->times(3)->andReturn($fileInfo);
        $entity->expects('getId')->andReturn($entityId = Uuid::v6());

        $this->doctrine->expects('persist')->times(2)->with($entity);
        $this->doctrine->expects('flush');

        $this->rootPathGenerator
            ->expects('__invoke')
            ->with($entity)
            ->andReturn('vfs://root/remotePath');

        $this->localFilesystem
            ->expects('createStream')
            ->with($pathName, 'r')
            ->andReturn($stream = $this->getTempResource());

        $this->remoteFilesystem
            ->expects('writeStream')
            ->with($remotePath, $stream)
            ->andReturnTrue();

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (EntityFileUpdateEvent $message) use ($entityId) {
                self::assertEquals($entityId, $message->entityId);

                return true;
            },
        ))->andReturns(new Envelope(new stdClass()));

        $result = $this->getStorageService()->storeEntity($splFileInfo, $entity);

        $this->assertTrue($result);
    }

    public function testFailedStoreEntity(): void
    {
        $splFileInfo = Mockery::mock(UploadedFile::class);
        $splFileInfo->expects('getClientOriginalName')->andReturn('clientName.pdf');
        $splFileInfo->expects('getPathname')->andReturn($pathName = 'vfs://root/someDir/clientName.pdf');

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo->getHash')->andReturnNull();

        $this->rootPathGenerator
            ->expects('__invoke')
            ->with($entity)
            ->andReturn('vfs://root/remotePath');

        $this->localFilesystem
            ->expects('createStream')
            ->with($pathName, 'r')
            ->andReturnFalse();

        $result = $this->getStorageService()->storeEntity($splFileInfo, $entity);

        $this->assertFalse($result);
    }

    public function testDownload(): void
    {
        $this->localFilesystem
            ->expects('createTempFile')
            ->andReturn($localPath = 'localPath');

        $this->remoteFilesystem
            ->expects('readStream')
            ->with($remotePath = 'remotePath')
            ->andReturn($remoteStream = $this->getTempResource());

        $this->localFilesystem
            ->expects('createStream')
            ->with($localPath, 'w')
            ->andReturn($localStream = $this->getTempResource());

        $this->localFilesystem
            ->expects('copy')
            ->with($remoteStream, $localStream)
            ->andReturnTrue();

        $result = $this->getStorageService()->download($remotePath);

        $this->assertSame($localPath, $result);
    }

    public function testDownloadWhenLocal(): void
    {
        $this->localFilesystem->shouldNotReceive('createTempFile');
        $this->remoteFilesystem->shouldNotReceive('readStream');
        $this->localFilesystem->shouldNotReceive('createStream');
        $this->localFilesystem->shouldNotReceive('copy');

        $expectedResult = sprintf('%s/%s', $documentRoot = 'documentRoot', $remotePath = 'remotePath');

        $result = $this->getStorageService(isLocal: true, documentRoot: $documentRoot)->download($remotePath);

        $this->assertSame($expectedResult, $result);
    }

    public function testDownloadWhenFailingToCreateTempFile(): void
    {
        $this->localFilesystem
            ->expects('createTempFile')
            ->andReturnFalse();

        $this->remoteFilesystem->shouldNotReceive('readStream');
        $this->localFilesystem->shouldNotReceive('createStream');
        $this->localFilesystem->shouldNotReceive('copy');

        $result = $this->getStorageService()->download('remotePath');

        $this->assertFalse($result);
    }

    public function testDownloadWithFailedRetrieval(): void
    {
        $this->localFilesystem
            ->expects('createTempFile')
            ->andReturn($localPath = 'localPath');

        $service = $this->getStorageService();
        $service
            ->expects('retrieve')
            ->with($remotePath = 'remotePath', $localPath)
            ->andReturnFalse();

        $this->localFilesystem
            ->expects('deleteFile')
            ->with($localPath);

        $result = $service->download($remotePath);

        $this->assertFalse($result);
    }

    public function testDownloadEntity(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);

        $entity = Mockery::mock(EntityWithFileInfo::class);

        $service = $this->getStorageService();
        $service
            ->expects('generateEntityPath')
            ->withSomeOfArgs($entity)
            ->andReturn($remotePath = 'pagePath');
        $service->expects('download')->with($remotePath)->andReturn($localPath = 'localPath');

        $result = $service->downloadEntity($entity);

        $this->assertSame($localPath, $result);
    }

    public function testRemoveDownload(): void
    {
        $this->localFilesystem
            ->expects('deleteFile')
            ->andReturn($localPath = 'localPath');

        $this->getStorageService()->removeDownload($localPath);
    }

    public function testRemoveDownloadWhenLocal(): void
    {
        $this->localFilesystem->shouldNotReceive('deleteFile');

        $this->getStorageService(isLocal: true)->removeDownload('localPath');
    }

    public function testDeleteAllFilesForEntity(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('isUploaded')->andReturnTrue();

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);
        $entity->expects('getId')->andReturn($entityId = Uuid::v6());

        $service = $this->getStorageService();
        $service
            ->expects('generateEntityPath')
            ->withSomeOfArgs($entity)
            ->andReturn($remotePath = 'pagePath');
        $this->remoteFilesystem->expects('delete')->with($remotePath)->andReturnTrue();

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (EntityFileUpdateEvent $message) use ($entityId) {
                self::assertEquals($entityId, $message->entityId);

                return true;
            },
        ))->andReturns(new Envelope(new stdClass()));

        $result = $service->deleteAllFilesForEntity($entity);

        $this->assertTrue($result);
    }

    public function testDeleteAllFilesForEntityWhenNotUploaded(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('isUploaded')->andReturnFalse();

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $service = $this->getStorageService();
        $service->shouldNotReceive('generateEntityPath');
        $service->shouldNotReceive('doDeleteAllFilesForEntity');

        $result = $service->deleteAllFilesForEntity($entity);

        $this->assertTrue($result);
    }

    public function testSetHashSuccessFul(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('setHash');

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $service = $this->getStorageService();

        $this->doctrine->expects('persist');
        $this->doctrine->expects('flush');

        $service->setHash($entity, __FILE__);
    }

    public function testSetHashThrowsExceptionWhenFileIsNotReadable(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $service = $this->getStorageService();

        $this->expectException(RuntimeException::class);
        $service->setHash($entity, 'foo/bar.txt');
    }

    private function getStorageService(
        bool $isLocal = false,
        string $documentRoot = 'documentRoot',
    ): EntityStorageService&MockInterface {
        return Mockery::mock(EntityStorageService::class, [
            $this->remoteFilesystem,
            $this->localFilesystem,
            $this->logger,
            $this->rootPathGenerator,
            $this->messageBus,
            $this->doctrine,
            $isLocal,
            $documentRoot,
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
