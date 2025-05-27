<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\FileStorage\Checker;

use App\Domain\FileStorage\Checker\FileStorageLister;
use App\Domain\FileStorage\Checker\FileStorageType;
use App\Tests\Unit\UnitTestCase;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Mockery\MockInterface;

class FileStorageListerTest extends UnitTestCase
{
    private FileStorageLister $fileStorageLister;
    private FilesystemOperator&MockInterface $documentStorage;
    private FilesystemOperator&MockInterface $batchStorage;

    public function setUp(): void
    {
        $this->fileStorageLister = new FileStorageLister(
            $this->documentStorage = \Mockery::mock(FilesystemOperator::class),
            $this->batchStorage = \Mockery::mock(FilesystemOperator::class),
        );
    }

    public function testPathsForDocumentStorage(): void
    {
        $this->documentStorage
            ->expects('listContents->filter')
            ->andReturn(
                new DirectoryListing([
                    $fileA = \Mockery::mock(StorageAttributes::class),
                    $fileB = \Mockery::mock(StorageAttributes::class),
                ]),
            );

        $fileA->shouldReceive('path')->andReturn('file/a');
        $fileA->shouldReceive('offsetGet')->with(StorageAttributes::ATTRIBUTE_FILE_SIZE)->andReturn(100);

        $fileB->shouldReceive('path')->andReturn('file/b');
        $fileB->shouldReceive('offsetGet')->with(StorageAttributes::ATTRIBUTE_FILE_SIZE)->andReturn(200);

        self::assertEquals(
            [
                '/file/a' => 100,
                '/file/b' => 200,
            ],
            iterator_to_array(
                $this->fileStorageLister->paths(FileStorageType::DOCUMENT),
            ),
        );
    }

    public function testPathsForBatchStorage(): void
    {
        $this->batchStorage
            ->expects('listContents->filter')
            ->andReturn(
                new DirectoryListing([
                    $fileA = \Mockery::mock(StorageAttributes::class),
                    $fileB = \Mockery::mock(StorageAttributes::class),
                ]),
            );

        $fileA->shouldReceive('path')->andReturn('file/a');
        $fileA->shouldReceive('offsetGet')->with(StorageAttributes::ATTRIBUTE_FILE_SIZE)->andReturn(100);

        $fileB->shouldReceive('path')->andReturn('file/b');
        $fileB->shouldReceive('offsetGet')->with(StorageAttributes::ATTRIBUTE_FILE_SIZE)->andReturn(200);

        self::assertEquals(
            [
                '/file/a' => 100,
                '/file/b' => 200,
            ],
            iterator_to_array(
                $this->fileStorageLister->paths(FileStorageType::BATCH),
            ),
        );
    }
}
