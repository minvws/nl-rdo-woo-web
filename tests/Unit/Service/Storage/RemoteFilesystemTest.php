<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Storage;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnreadableFileEncountered;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Service\Storage\RemoteFilesystem;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

use function fopen;

final class RemoteFilesystemTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private FilesystemOperator&MockInterface $documentStorage;

    protected function setUp(): void
    {
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->documentStorage = Mockery::mock(FilesystemOperator::class);
    }

    public function testReadStream(): void
    {
        $this->documentStorage
            ->expects('readStream')
            ->with($location = 'remotePath')
            ->andReturn($resoure = $this->getTempResource());

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->readStream($location);

        $this->assertSame($resoure, $result);
    }

    public function testFailedReadStream(): void
    {
        $this->documentStorage
            ->expects('readStream')
            ->with($location = 'remotePath')
            ->andThrow(new UnreadableFileEncountered($exceptionMessage = 'My Exception message'));

        $this->logger
            ->expects('error')
            ->with('Could not read file stream from storage adapter', [
                'location' => $location,
                'exception' => $exceptionMessage,
            ]);

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->readStream($location);

        $this->assertNull($result);
    }

    public function testDirectoryExists(): void
    {
        $this->documentStorage
            ->expects('directoryExists')
            ->with($location = 'foo/bar')
            ->andReturnTrue();

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->directoryExists($location);

        $this->assertTrue($result, 'The directory should exist');
    }

    public function testCreateDirectory(): void
    {
        $this->documentStorage
            ->expects('createDirectory')
            ->with($location = 'foo/bar')
            ->andReturnTrue();

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->createDirectory($location);

        $this->assertTrue($result);
    }

    public function testFailedCreateDirectory(): void
    {
        $this->documentStorage
            ->expects('createDirectory')
            ->with($location = 'foo/bar')
            ->andThrow(new UnreadableFileEncountered($exceptionMessage = 'My Exception message'));

        $this->logger
            ->expects('error')
            ->with('Could not create directory in storage', [
                'location' => $location,
                'exception' => $exceptionMessage,
            ]);

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->createDirectory($location);

        $this->assertFalse($result);
    }

    public function testCreateDirectoryIfNotExistsWithNonExistingDir(): void
    {
        $remoteFilesystem = $this->getInstance();
        $remoteFilesystem
            ->expects('directoryExists')
            ->with($location = 'foo/bar')
            ->andReturnFalse();
        $remoteFilesystem
            ->expects('createDirectory')
            ->with($location)
            ->andReturnTrue();

        $result = $remoteFilesystem->createDirectoryIfNotExist($location);

        $this->assertTrue($result);
    }

    public function testCreateDirectoryIfNotExistsWithExistingDir(): void
    {
        $remoteFilesystem = $this->getInstance();
        $remoteFilesystem
            ->expects('directoryExists')
            ->with($location = 'foo/bar')
            ->andReturnTrue();
        $remoteFilesystem->shouldNotReceive('createDirectory');

        $result = $remoteFilesystem->createDirectoryIfNotExist($location);

        $this->assertTrue($result);
    }

    public function testFileExists(): void
    {
        $this->documentStorage
            ->expects('fileExists')
            ->with($location = 'foo/bar')
            ->andReturnTrue();

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->fileExists($location);

        $this->assertTrue($result);
    }

    public function testFailedFileExists(): void
    {
        $this->documentStorage
            ->expects('fileExists')
            ->with($location = 'foo/bar')
            ->andThrow(new UnreadableFileEncountered($exceptionMessage = 'My Exception message'));

        $this->logger
            ->expects('error')
            ->with('Could not check if file exists', [
                'location' => $location,
                'exception' => $exceptionMessage,
            ]);

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->fileExists($location);

        $this->assertFalse($result);
    }

    public function testFileSize(): void
    {
        $this->documentStorage
            ->expects('fileSize')
            ->with($remotePath = 'foo/bar')
            ->andReturn($size = 337);

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->fileSize($remotePath);

        $this->assertSame($size, $result);
    }

    public function testFailedFileSize(): void
    {
        $this->documentStorage
            ->expects('fileSize')
            ->with($path = 'foo/bar')
            ->andThrow(new UnreadableFileEncountered($exceptionMessage = 'My Exception message'));

        $this->logger
            ->expects('error')
            ->with('Could not check file size', [
                'path' => $path,
                'exception' => $exceptionMessage,
            ]);

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->fileSize($path);

        $this->assertSame(0, $result);
    }

    public function testDelete(): void
    {
        $this->documentStorage
            ->expects('delete')
            ->with($location = 'foo/bar')
            ->andReturnTrue();

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->delete($location);

        $this->assertTrue($result);
    }

    public function testFailedDelete(): void
    {
        $this->documentStorage
            ->expects('delete')
            ->with($location = 'foo/bar')
            ->andThrow(new UnreadableFileEncountered($exceptionMessage = 'My Exception message'));

        $this->logger
            ->expects('error')
            ->with('Could not delete file from storage for entity', [
                'location' => $location,
                'exception' => $exceptionMessage,
            ]);

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->delete($location);

        $this->assertFalse($result);
    }

    public function testWriteStream(): void
    {
        $this->documentStorage
            ->expects('writeStream')
            ->with($location = 'foo/bar/input.txt', $resource = $this->getTempResource());

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->writeStream($location, $resource);

        $this->assertTrue($result);
    }

    public function testFailedWriteStream(): void
    {
        $this->documentStorage
            ->expects('writeStream')
            ->with($location = 'foo/bar/input.txt', $resource = $this->getTempResource())
            ->andThrow(new UnreadableFileEncountered($exceptionMessage = 'My Exception message'));

        $this->logger
            ->expects('error')
            ->with('Could not write file to storage adapter', [
                'location' => $location,
                'exception' => $exceptionMessage,
            ]);

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->writeStream($location, $resource);

        $this->assertFalse($result);
    }

    public function testWrite(): void
    {
        $this->documentStorage
            ->expects('write')
            ->with($location = 'foo/bar/input.txt', $content = 'my content');

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->write($location, $content);

        $this->assertTrue($result);
    }

    public function testFailedWrite(): void
    {
        $this->documentStorage
            ->expects('write')
            ->with($location = 'foo/bar/input.txt', $content = 'my content')
            ->andThrow(new UnreadableFileEncountered($exceptionMessage = 'My Exception message'));

        $this->logger
            ->expects('error')
            ->with('Could not write contents to storage adapter', [
                'location' => $location,
                'exception' => $exceptionMessage,
            ]);

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->write($location, $content);

        $this->assertFalse($result);
    }

    public function testRead(): void
    {
        $this->documentStorage
            ->expects('read')
            ->with($location = 'foo/bar/foobar.txt')
            ->andReturn($content = 'some content');

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->read($location);

        $this->assertSame($content, $result);
    }

    public function testFailedRead(): void
    {
        $this->documentStorage
            ->expects('read')
            ->with($location = 'foo/bar/foobar.txt')
            ->andThrow(new UnreadableFileEncountered($exceptionMessage = 'My Exception message'));

        $this->logger
            ->expects('error')
            ->with('Could not read contents from storage adapter', [
                'location' => $location,
                'exception' => $exceptionMessage,
            ]);

        $remoteFilesystem = $this->getInstance();
        $result = $remoteFilesystem->read($location);

        $this->assertFalse($result);
    }

    private function getInstance(): RemoteFilesystem&MockInterface
    {
        return Mockery::mock(RemoteFilesystem::class, [$this->logger, $this->documentStorage])
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
