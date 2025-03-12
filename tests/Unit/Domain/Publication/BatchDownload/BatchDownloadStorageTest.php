<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\BatchDownload;

use App\Domain\Publication\BatchDownload\BatchDownload;
use App\Domain\Publication\BatchDownload\BatchDownloadStorage;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class BatchDownloadStorageTest extends MockeryTestCase
{
    private BatchDownloadStorage $storage;
    private LoggerInterface&MockInterface $logger;
    private FilesystemOperator&MockInterface $filesystemOperator;
    private vfsStreamDirectory $virtualFilesystem;

    public function setUp(): void
    {
        $this->filesystemOperator = \Mockery::mock(FilesystemOperator::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);

        $this->virtualFilesystem = vfsStream::setup(
            structure: [
                'foo' => [
                    'bar.zip' => 'dummy data',
                ],
            ],
        );

        $this->storage = new BatchDownloadStorage(
            $this->filesystemOperator,
            $this->logger,
        );
    }

    public function testGetFileStreamForBatch(): void
    {
        $batch = \Mockery::mock(BatchDownload::class);
        $batch
            ->shouldReceive('getFilename')
            ->andReturn($filename = 'foo/bar.zip');

        $stream = \Mockery::mock(StreamInterface::class);

        $this->filesystemOperator
            ->expects('readStream')
            ->with($filename)
            ->andReturn($stream);

        self::assertSame(
            $stream,
            $this->storage->getFileStreamForBatch($batch),
        );
    }

    public function testGetFileStreamForBatchReturnsFalseOnFailure(): void
    {
        $batch = \Mockery::mock(BatchDownload::class);
        $batch
            ->shouldReceive('getFilename')
            ->andReturn($filename = 'foo/bar.pdf');
        $batch
            ->shouldReceive('getId')
            ->andReturn(Uuid::v6());

        $this->filesystemOperator
            ->expects('readStream')
            ->with($filename)
            ->andThrow(new UnableToReadFile());

        $this->logger->expects('error');

        self::assertFalse(
            $this->storage->getFileStreamForBatch($batch),
        );
    }

    public function testRemoveFileForBatch(): void
    {
        $batch = \Mockery::mock(BatchDownload::class);
        $batch
            ->shouldReceive('getFilename')
            ->andReturn($filename = 'foo/bar.pdf');

        $this->filesystemOperator
            ->expects('delete')
            ->with($filename);

        self::assertTrue(
            $this->storage->removeFileForBatch($batch),
        );
    }

    public function testRemoveFileForBatchReturnsFalseOnError(): void
    {
        $batch = \Mockery::mock(BatchDownload::class);
        $batch
            ->shouldReceive('getFilename')
            ->andReturn($filename = 'foo/bar.pdf');
        $batch
            ->shouldReceive('getId')
            ->andReturn(Uuid::v6());

        $this->filesystemOperator
            ->expects('delete')
            ->with($filename)
            ->andThrow(new UnableToDeleteFile());

        $this->logger->expects('error');

        self::assertFalse(
            $this->storage->removeFileForBatch($batch),
        );
    }

    public function testAdd(): void
    {
        $sourcePath = $this->virtualFilesystem->getChild('foo/bar.zip')->url();
        $destinationPath = '123/456.zip';

        $this->filesystemOperator
            ->expects('writeStream')
            ->with($destinationPath, \Mockery::type('resource'));

        self::assertTrue(
            $this->storage->add($sourcePath, $destinationPath),
        );
    }

    public function testAddReturnsFalseOnWriteError(): void
    {
        $sourcePath = $this->virtualFilesystem->getChild('foo/bar.zip')->url();
        $destinationPath = '123/456.zip';

        $this->filesystemOperator
            ->expects('writeStream')
            ->with($destinationPath, \Mockery::type('resource'))
            ->andThrow(new UnableToWriteFile());

        $this->logger->expects('error');

        self::assertFalse(
            $this->storage->add($sourcePath, $destinationPath),
        );
    }
}
