<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Storage;

use App\Entity\Document;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class DocumentStorageTest extends MockeryTestCase
{
    private vfsStreamDirectory $vfs;
    private EntityManagerInterface&MockInterface $doctrineMock;
    private FilesystemOperator&MockInterface $storageMock;
    private LoggerInterface&MockInterface $loggerMock;
    private Filesystem $storage;

    public function setUp(): void
    {
        ini_set('allow_url_fopen', true);
        vfsStreamWrapper::register();
        $this->vfs = vfsStream::setup();

        $this->doctrineMock = \Mockery::mock(EntityManagerInterface::class);
        $this->storageMock = \Mockery::mock(FilesystemOperator::class);
        $this->storage = new Filesystem(new InMemoryFilesystemAdapter());
        $this->loggerMock = \Mockery::mock(LoggerInterface::class);
    }

    public function testStoreRetrieveExistsFile(): void
    {
        $service = $this->getStorageService(true, 'phpunit/test/');

        self::assertFalse($service->exists('/dir/to/store/file.txt'));

        $localFile = $this->createFile('/test.txt', 'testcontent');
        $service->store($localFile, '/dir/to/store/file.txt');

        self::assertTrue($service->exists('/dir/to/store/file.txt'));
        self::assertFalse($service->exists('/test.txt'));

        $service->retrieve('/dir/to/store/file.txt', $this->vfs->url() . '/newfile.txt');
        self::assertFileExists($this->vfs->url() . '/newfile.txt');
        self::assertEquals('testcontent', file_get_contents($this->vfs->url() . '/newfile.txt'));
    }

    public function testErrorsWhileOpening(): void
    {
        $service = $this->getStorageService(true, 'phpunit/test/');

        $this->loggerMock->expects('error')->withSomeOfArgs('Could not open local file stream');
        self::assertFalse($service->store(new \SplFileInfo('/doesnotexist'), '/dir/to/store/file.txt'));
    }

    public function testRetrieve(): void
    {
        $service = $this->getStorageService(true, 'phpunit/test/');

        $this->loggerMock->expects('error')->withSomeOfArgs('Could not read file stream from storage adapter');
        self::assertFalse($service->retrieve('blaat:///remote/not/existing/path', 'test.txt'));
    }

    public function testFlysystemException(): void
    {
        $service = $this->getStorageService(useStorageMock: true);

        $this->storageMock->expects('readStream')->andThrow(new \Exception());
        $this->loggerMock->expects('error')->withSomeOfArgs('Could not read file stream from storage adapter');

        self::assertFalse($service->retrieve('/doesnotexist', 'notexisting.txt'));
    }

    public function testFailToRetrieveToIncorrectLocalPath(): void
    {
        $service = $this->getStorageService();

        $localFile = $this->createFile('/test.txt', 'testcontent');
        $service->store($localFile, '/dir/to/store/file.txt');

        $this->loggerMock->expects('error')->withSomeOfArgs('Could not open local path for writing');
        self::assertFalse($service->retrieve('/dir/to/store/file.txt', 'xxx://2&&&&>>>)()()!!!>>??wrongfilename.txt'));
    }

    public function testExistsWorks(): void
    {
        $service = $this->getStorageService();

        $localFile = $this->createFile('/test.txt', 'testcontent');
        $service->store($localFile, '/dir/to/store/file.txt');

        self::assertFalse($service->exists('/not/existing/file.txt'));
        self::assertTrue($service->exists('/dir/to/store/file.txt'));
    }

    public function testExistsFailsInFlysystem(): void
    {
        $service = $this->getStorageService(useStorageMock: true);
        $this->storageMock->expects('fileExists')->andThrows(new \Exception());
        $this->loggerMock->expects('error')->withSomeOfArgs('Could not check if file exists in storage adapter');

        self::assertFalse($service->exists('/dir/to/store/file.txt'));
    }

    public function testStoreInvalidLocalFile(): void
    {
        $service = $this->getStorageService(useStorageMock: true);

        $file = new \SplFileInfo('xxx://invalidfile.txt');

        $this->loggerMock->expects('error')->withSomeOfArgs('Could not open local file stream');
        self::assertFalse($service->store($file, '/store/in/here.txt'));
    }

    public function testStoreWithFlySystemFailure(): void
    {
        $service = $this->getStorageService(useStorageMock: true);
        $this->storageMock->expects('writeStream')->andThrows(new \Exception());

        $localFile = $this->createFile('/test.txt', 'testcontent');

        $this->loggerMock->expects('error')->withSomeOfArgs('Could not write file to storage adapter');
        self::assertFalse($service->store($localFile, '/store/in/here.txt'));
    }

    public function testRetrievePage(): void
    {
        $service = $this->getStorageService(useStorageMock: true);

        $this->storageMock->expects('readStream')->with('/62/7fe00a061dbf9d522961aa569b1061f905801b8b060ca261ab5aba40cd212c/pages/page-1.pdf');

        $document = \Mockery::mock(Document::class);
        $document->expects('getId')->andReturns(new Uuid('04d3fb60-95cf-4a56-9e3e-bad0f62c7cce'));

        self::assertFalse($service->retrievePage($document, 1, 'localpath.txt'));
    }

    public function testRetrieveDocument(): void
    {
        $service = $this->getStorageService(useStorageMock: true);

        $this->storageMock->expects('readStream')->with('/62/7fe00a061dbf9d522961aa569b1061f905801b8b060ca261ab5aba40cd212c/filepath.txt');

        $document = \Mockery::mock(Document::class);
        $document->expects('getId')->andReturns(new Uuid('04d3fb60-95cf-4a56-9e3e-bad0f62c7cce'));
        $document->expects('getFileInfo->getPath')->andReturn('filepath.txt');

        self::assertFalse($service->retrieveDocument($document, 'localpath.txt'));
    }

    public function testDownloadOnLocalFilesystem(): void
    {
        $service = $this->getStorageService(true, 'phpunit/test');

        $localFile = $this->createFile('/test.txt', 'testcontent');
        $service->store($localFile, 'dir/to/store/file.txt');

        $tempFile = strval($service->download('dir/to/store/file.txt'));
        self::assertEquals('phpunit/test/dir/to/store/file.txt', $tempFile);

        $service->removeDownload($tempFile);
        self::assertTrue($service->exists('dir/to/store/file.txt'));
    }

    public function testDownloadOnRemoteFilesystem(): void
    {
        $service = $this->getStorageService(false, 'phpunit/test/');

        $localFile = $this->createFile('/test.txt', 'testcontent');
        $service->store($localFile, '/dir/to/store/file.txt');

        $tempFile = strval($service->download('/dir/to/store/file.txt'));

        self::assertStringContainsString(sys_get_temp_dir() . '/woopie', $tempFile);
        self::assertFileExists($tempFile);
        self::assertEquals('testcontent', file_get_contents($tempFile));

        $service->removeDownload($tempFile);
        self::assertFileDoesNotExist($tempFile);
    }

    protected function getStorageService($isLocal = false, $documentRoot = '', bool $useStorageMock = false): DocumentStorageService
    {
        return new DocumentStorageService(
            $this->doctrineMock,
            $useStorageMock ? $this->storageMock : $this->storage,
            $this->loggerMock,
            $isLocal,
            $documentRoot
        );
    }

    protected function createFile(string $path, string $content): \SplFileInfo
    {
        $path = $this->vfs->url() . $path;
        $tmp = file_put_contents($path, $content);
        if ($tmp === false) {
            throw new \Exception("Error creating file $path");
        }

        return new \SplFileInfo($path);
    }
}
