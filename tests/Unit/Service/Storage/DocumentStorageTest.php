<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Storage;

use App\Entity\Document;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class DocumentStorageTest extends MockeryTestCase
{
    protected vfsStreamDirectory $vfs;
    protected Mockery\LegacyMockInterface|EntityManagerInterface|Mockery\MockInterface $doctrineMock;
    protected Mockery\LegacyMockInterface|FilesystemOperator|Mockery\MockInterface $storageMock;
    protected LoggerInterface|Mockery\LegacyMockInterface|Mockery\MockInterface $loggerMock;

    public function setUp(): void
    {
        ini_set('allow_url_fopen', true);
        vfsStreamWrapper::register();
        $this->vfs = vfsStream::setup('root');
    }

    public function testStoreRetrieveExistsFile()
    {
        $service = $this->getStorageService(true, 'phpunit/test/');

        $this->assertFalse($service->exists('/dir/to/store/file.txt'));

        $localFile = $this->createFile('/test.txt', 'testcontent');
        $service->store($localFile, '/dir/to/store/file.txt');

        $this->assertTrue($service->exists('/dir/to/store/file.txt'));
        $this->assertFalse($service->exists('/test.txt'));

        $service->retrieve('/dir/to/store/file.txt', $this->vfs->url() . '/newfile.txt');
        $this->assertTrue(file_exists($this->vfs->url() . '/newfile.txt'));
        $this->assertEquals('testcontent', file_get_contents($this->vfs->url() . '/newfile.txt'));

        //        print_r(vfsStream::inspect(new vfsStreamStructureVisitor(), $this->root)->getStructure());
    }

    public function testErrorsWhileOpening()
    {
        $service = $this->getStorageService(true, 'phpunit/test/');

        $this->loggerMock->expects('error')->once()->withSomeOfArgs('Could not open local file stream');
        $this->assertFalse($service->store(new \SplFileInfo('/doesnotexist'), '/dir/to/store/file.txt'));
    }

    public function testRetrieve()
    {
        $service = $this->getStorageService(true, 'phpunit/test/');

        $this->loggerMock->expects('error')->once()->withSomeOfArgs('Could not read file stream from storage adapter');
        $this->assertFalse($service->retrieve('blaat:///remote/not/existing/path', 'test.txt'));
    }

    public function testFlysystemException()
    {
        $service = $this->getStorageService(mock: true);

        $this->storageMock->expects('readStream')->once()->andThrow(new \Exception());
        $this->loggerMock->expects('error')->once()->withSomeOfArgs('Could not read file stream from storage adapter');

        $this->assertFalse($service->retrieve('/doesnotexist', 'notexisting.txt'));
    }

    public function testFailToRetrieveToIncorrectLocalPath()
    {
        $service = $this->getStorageService();

        $localFile = $this->createFile('/test.txt', 'testcontent');
        $service->store($localFile, '/dir/to/store/file.txt');

        $this->loggerMock->expects('error')->once()->withSomeOfArgs('Could not open local path for writing');
        $this->assertFalse($service->retrieve('/dir/to/store/file.txt', 'xxx://2&&&&>>>)()()!!!>>??wrongfilename.txt'));
    }

    public function testExistsWorks()
    {
        $service = $this->getStorageService();

        $localFile = $this->createFile('/test.txt', 'testcontent');
        $service->store($localFile, '/dir/to/store/file.txt');

        $this->assertFalse($service->exists('/not/existing/file.txt'));
        $this->assertTrue($service->exists('/dir/to/store/file.txt'));
    }

    public function testExistsFailsInFlysystem()
    {
        $service = $this->getStorageService(mock: true);
        $this->storageMock->expects('fileExists')->once()->andThrows(new \Exception());
        $this->loggerMock->expects('error')->once()->withSomeOfArgs('Could not check if file exists in storage adapter');

        $this->assertFalse($service->exists('/dir/to/store/file.txt'));
    }

    public function testStoreInvalidLocalFile()
    {
        $service = $this->getStorageService(mock: true);

        $file = new \SplFileInfo('xxx://invalidfile.txt');

        $this->loggerMock->expects('error')->once()->withSomeOfArgs('Could not open local file stream');
        $this->assertFalse($service->store($file, '/store/in/here.txt'));
    }

    public function testStoreWithFlySystemFailure()
    {
        $service = $this->getStorageService(mock: true);
        $this->storageMock->expects('writeStream')->once()->andThrows(new \Exception());

        $localFile = $this->createFile('/test.txt', 'testcontent');

        $this->loggerMock->expects('error')->once()->withSomeOfArgs('Could not write file to storage adapter');
        $this->assertFalse($service->store($localFile, '/store/in/here.txt'));
    }

    public function testRetrievePage()
    {
        $service = $this->getStorageService(mock: true);

        $this->storageMock->expects('readStream')->once()->with('/62/7fe00a061dbf9d522961aa569b1061f905801b8b060ca261ab5aba40cd212c/pages/page-1.pdf');

        $document = new Document();
        $document->setId(new Uuid('04d3fb60-95cf-4a56-9e3e-bad0f62c7cce'));

        $this->assertFalse($service->retrievePage($document, 1, 'localpath.txt'));
    }

    public function testRetrieveDocument()
    {
        $service = $this->getStorageService(mock: true);

        $this->storageMock->expects('readStream')->once()->with('/62/7fe00a061dbf9d522961aa569b1061f905801b8b060ca261ab5aba40cd212c/filepath.txt');

        $document = new Document();
        $document->setId(new Uuid('04d3fb60-95cf-4a56-9e3e-bad0f62c7cce'));
        $document->setFilepath('filepath.txt');
        $this->assertFalse($service->retrieveDocument($document, 'localpath.txt'));
    }

    public function testDownloadOnLocalFilesystem()
    {
        $service = $this->getStorageService(true, 'phpunit/test');

        $localFile = $this->createFile('/test.txt', 'testcontent');
        $service->store($localFile, 'dir/to/store/file.txt');

        $tempFile = $service->download('dir/to/store/file.txt');
        $this->assertEquals('phpunit/test/dir/to/store/file.txt', $tempFile);

        $service->removeDownload($tempFile);
        $this->assertTrue($service->exists('dir/to/store/file.txt'));
    }

    public function testDownloadOnRemoteFilesystem()
    {
        $service = $this->getStorageService(false, 'phpunit/test/');

        $localFile = $this->createFile('/test.txt', 'testcontent');
        $service->store($localFile, '/dir/to/store/file.txt');

        $tempFile = $service->download('/dir/to/store/file.txt');
        $this->assertStringContainsString(sys_get_temp_dir() . '/woopie', $tempFile);
        $this->assertFileExists($tempFile);
        $this->assertEquals('testcontent', file_get_contents($tempFile));

        $service->removeDownload($tempFile);
        $this->assertFileDoesNotExist($tempFile);
    }

    protected function getStorageService($isLocal = false, $documentRoot = '', bool $mock = false): DocumentStorageService
    {
        $this->doctrineMock = \Mockery::mock(EntityManagerInterface::class);
        if ($mock) {
            $this->storageMock = \Mockery::mock(FilesystemOperator::class);
        } else {
            $this->storageMock = new Filesystem(new InMemoryFilesystemAdapter());
        }
        $this->loggerMock = \Mockery::mock(LoggerInterface::class);

        return new DocumentStorageService(
            $this->doctrineMock,
            $this->storageMock,
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

    protected function list()
    {
        foreach ($this->storageMock->listContents('/', true) as $file) {
            print $file->path() . "\n";
        }
    }
}
