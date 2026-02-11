<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\FileStorage;

use Aws\S3\S3Client;
use League\Flysystem\FilesystemOperator;
use Mockery;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Shared\Domain\FileStorage\Checker\FileStorageLister;
use Shared\Domain\FileStorage\Checker\FileStorageType;
use Shared\Domain\FileStorage\Checker\OrphanedPaths;
use Shared\Domain\FileStorage\OrphanedFileMover;
use Shared\Tests\Unit\UnitTestCase;

use function fopen;

class OrphanedFileMoverTest extends UnitTestCase
{
    private OrphanedFileMover $mover;
    private FileStorageLister&MockInterface $fileStorageLister;
    private S3Client&MockInterface $s3client;
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup();

        $this->mover = new OrphanedFileMover(
            $this->fileStorageLister = Mockery::mock(FileStorageLister::class),
            $this->s3client = Mockery::mock(S3Client::class),
        );
    }

    public function testMove(): void
    {
        $paths = Mockery::mock(OrphanedPaths::class);
        $paths->paths = [
            FileStorageType::DOCUMENT->value => [
                $docA = 'foo/a.doc',
                $docB = 'bar/b.doc',
            ],
            FileStorageType::BATCH->value => [
                $batchA = 'foo/a.zip',
            ],
        ];

        $ticketCalls = 0;
        $targetBucket = 'trash';
        $callable = function () use (&$ticketCalls): void {
            $ticketCalls++;
        };

        $documentStorage = Mockery::mock(FilesystemOperator::class);
        $documentStorage->expects('readStream')->with($docA)->andReturn($docAStream = $this->getStream($docA));
        $documentStorage->expects('readStream')->with($docB)->andReturn($docBStream = $this->getStream($docB));
        $documentStorage->expects('delete')->with($docA);
        $documentStorage->expects('delete')->with($docB);

        $this->fileStorageLister
            ->expects('getFilesystem')
            ->with(FileStorageType::DOCUMENT)
            ->twice()
            ->andReturn($documentStorage);

        $batchStorage = Mockery::mock(FilesystemOperator::class);
        $batchStorage->expects('readStream')->with($batchA)->andReturn($batchAStream = $this->getStream($batchA));
        $batchStorage->expects('delete')->with($batchA);

        $this->fileStorageLister
            ->expects('getFilesystem')
            ->with(FileStorageType::BATCH)
            ->andReturn($batchStorage);

        $this->s3client->expects('upload')->with($targetBucket, 'document/' . $docA, $docAStream, 'private', Mockery::any());
        $this->s3client->expects('upload')->with($targetBucket, 'document/' . $docB, $docBStream, 'private', Mockery::any());
        $this->s3client->expects('upload')->with($targetBucket, 'batch/' . $batchA, $batchAStream, 'private', Mockery::any());

        $this->mover->move($paths, $targetBucket, $callable);

        self::assertEquals(3, $ticketCalls);
    }

    /**
     * @return resource
     */
    private function getStream(string $docA)
    {
        $docAFile = vfsStream::newFile($docA)->withContent($docA)->at($this->root);
        $stream = fopen($docAFile->url(), 'r');

        self::assertNotFalse($stream);

        return $stream;
    }
}
