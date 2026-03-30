<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\BatchDownload\Archiver;

use Aws\CommandInterface;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\BatchDownload\Archiver\ZipStreamBatchArchiver;
use Shared\Domain\Publication\BatchDownload\Archiver\ZipStreamFactory;
use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Shared\Domain\Publication\BatchDownload\Type\BatchDownloadTypeInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\S3\StreamFactory;
use Shared\Service\DownloadFilenameGenerator;
use Shared\Tests\Unit\UnitTestCase;
use ZipStream\Exception\FileNotReadableException;
use ZipStream\ZipStream;

final class ZipStreamBatchArchiverTest extends UnitTestCase
{
    private const string BATCH_BUCKET = 'batchBucket';
    private const string DOCUMENT_BUCKET = 'documentBucket';
    private const string BATCH_FILE_NAME = 'batch-name';

    private S3Client&MockInterface $s3Client;
    private ZipStreamFactory&MockInterface $zipStreamFactory;
    private DownloadFilenameGenerator&MockInterface $filenameGenerator;
    private LoggerInterface&MockInterface $logger;
    private StreamFactory&MockInterface $streamFactory;
    private ZipStreamBatchArchiver $batchArchiver;

    private StreamInterface&MockInterface $zipFile;
    private ZipStream&MockInterface $zipStream;

    protected function setUp(): void
    {
        parent::setUp();

        $this->s3Client = Mockery::mock(S3Client::class);
        $this->zipStreamFactory = Mockery::mock(ZipStreamFactory::class);
        $this->filenameGenerator = Mockery::mock(DownloadFilenameGenerator::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->streamFactory = Mockery::mock(StreamFactory::class);

        $this->zipFile = Mockery::mock(StreamInterface::class);
        $this->zipStream = Mockery::mock(ZipStream::class);

        $this->batchArchiver = new ZipStreamBatchArchiver(
            self::BATCH_BUCKET,
            self::DOCUMENT_BUCKET,
            $this->s3Client,
            $this->zipStreamFactory,
            $this->filenameGenerator,
            $this->logger,
            $this->streamFactory,
        );
    }

    public function testStart(): void
    {
        $batchDownload = Mockery::mock(BatchDownload::class);

        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);

        $this->s3Client->expects('registerStreamWrapperV2');
        $this->streamFactory
            ->expects('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->expects('create')
            ->with($this->zipFile)
            ->andReturn($this->zipStream);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);
    }

    public function testStartWithExceptionThrown(): void
    {
        $batchDownload = Mockery::mock(BatchDownload::class);

        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);

        $this->s3Client->expects('registerStreamWrapperV2');
        $this->streamFactory
            ->expects('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->expects('create')
            ->with($this->zipFile)
            ->andThrow($ex = new FileNotReadableException('my path'));

        $this->logger
            ->expects('error')
            ->with('"ZipStream\Exception\FileNotReadableException" exception thrown', [
                'exceptionMessage' => $ex->getMessage(),
            ]);

        $this->zipFile
            ->expects('close');

        $this->s3Client
            ->expects('deleteObject')->with(['Bucket' => self::BATCH_BUCKET, 'Key' => self::BATCH_FILE_NAME]);

        $this->expectExceptionObject($ex);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);
    }

    public function testStartWithExceptionThrownAndFailingToDeleteS3Object(): void
    {
        $batchDownload = Mockery::mock(BatchDownload::class);
        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);

        $this->s3Client->expects('registerStreamWrapperV2');
        $this->streamFactory
            ->expects('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->expects('create')
            ->with($this->zipFile)
            ->andThrow($ex1 = new FileNotReadableException('my path'));

        $this->logger
            ->expects('error')
            ->with('"ZipStream\Exception\FileNotReadableException" exception thrown', [
                'exceptionMessage' => $ex1->getMessage(),
            ]);

        $this->zipFile
            ->expects('close');

        $this->s3Client
            ->expects('deleteObject')
            ->with(['Bucket' => self::BATCH_BUCKET, 'Key' => self::BATCH_FILE_NAME])

            ->andThrow($ex2 = new S3Exception('failed to delete', Mockery::mock(CommandInterface::class)));

        $this->logger
            ->expects('error')
            ->with('"Aws\S3\Exception\S3Exception" exception thrown', [
                'exceptionMessage' => $ex2->getMessage(),
            ]);

        $this->expectExceptionObject($ex1);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);
    }

    public function testAddDocument(): void
    {
        $batchDownload = Mockery::mock(BatchDownload::class);

        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);

        $this->s3Client->expects('registerStreamWrapperV2');
        $this->streamFactory
            ->expects('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->expects('create')
            ->with($this->zipFile)
            ->andReturn($this->zipStream);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getPath')->andReturn('path');

        $path = 'path';
        $document = Mockery::mock(Document::class);
        $document->expects('getFileInfo')->andReturn($fileInfo);

        $documentResource = Mockery::mock(StreamInterface::class);

        $this->streamFactory
            ->expects('createReadOnlyStream')
            ->with(self::DOCUMENT_BUCKET, $path)

            ->andReturn($documentResource);

        $this->filenameGenerator
            ->expects('getFileName')
            ->with($document)
            ->andReturn($filename = 'filename');

        $this->zipStream
            ->expects('addFileFromPsr7Stream')
            ->with($filename, $documentResource);

        $documentResource->expects('close');

        $this->assertTrue($this->batchArchiver->addDocument($document));
    }

    public function testAddDocumentWithFailure(): void
    {
        $batchDownload = Mockery::mock(BatchDownload::class);

        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);

        $this->s3Client->expects('registerStreamWrapperV2');
        $this->streamFactory
            ->expects('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->expects('create')
            ->with($this->zipFile)
            ->andReturn($this->zipStream);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);

        $path = 'path';
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getPath')->andReturn($path);

        $document = Mockery::mock(Document::class);
        $document->expects('getFileInfo')->andReturn($fileInfo);

        $documentResource = Mockery::mock(StreamInterface::class);

        $this->streamFactory
            ->expects('createReadOnlyStream')
            ->with(self::DOCUMENT_BUCKET, $path)

            ->andReturn($documentResource);

        $this->filenameGenerator
            ->expects('getFileName')
            ->with($document)
            ->andReturn($filename = 'filename');

        $this->zipStream
            ->expects('addFileFromPsr7Stream')
            ->with($filename, $documentResource)

            ->andThrow($ex = new FileNotReadableException('my path'));

        $this->logger
            ->expects('error')
            ->with('"ZipStream\Exception\FileNotReadableException" exception thrown', [
                'exceptionMessage' => $ex->getMessage(),
            ]);

        $this->zipStream->expects('finish')->andReturn(0);

        $this->zipFile
            ->expects('close');

        $this->s3Client
            ->expects('deleteObject')
            ->with(['Bucket' => self::BATCH_BUCKET, 'Key' => self::BATCH_FILE_NAME]);

        $documentResource->expects('close');

        $this->assertFalse($this->batchArchiver->addDocument($document));
    }

    public function testFinish(): void
    {
        $batchDownload = Mockery::mock(BatchDownload::class);

        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);

        $this->s3Client->expects('registerStreamWrapperV2');
        $this->streamFactory
            ->expects('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->expects('create')
            ->with($this->zipFile)
            ->andReturn($this->zipStream);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);

        $this->zipStream
            ->expects('finish')
            ->andReturn($fileSize = 123);

        $this->zipFile
            ->expects('close');

        $result = $this->batchArchiver->finish();

        self::assertNotFalse($result);
        self::assertEquals(self::BATCH_FILE_NAME, $result->filename);
        self::assertEquals($fileSize, $result->size);
        self::assertEquals(0, $result->fileCount);
    }

    public function testFinishWithMultipleDocumentsAdded(): void
    {
        $batchDownload = Mockery::mock(BatchDownload::class);

        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);

        $this->s3Client->expects('registerStreamWrapperV2');
        $this->streamFactory
            ->expects('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->expects('create')
            ->with($this->zipFile)
            ->andReturn($this->zipStream);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);

        $documentResource = Mockery::mock(StreamInterface::class);
        $filename = 'filename';

        // document 1
        $pathOne = 'path-one';
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getPath')->andReturn($pathOne);

        $documentOne = Mockery::mock(Document::class);
        $documentOne->expects('getFileInfo')->andReturn($fileInfo);

        $this->streamFactory
            ->expects('createReadOnlyStream')
            ->with(self::DOCUMENT_BUCKET, $pathOne)
            ->andReturn($documentResource);

        $this->filenameGenerator
            ->expects('getFileName')
            ->with($documentOne)
            ->andReturn($filename);

        $this->zipStream
            ->expects('addFileFromPsr7Stream')
            ->with($filename, $documentResource);

        $documentResource->expects('close');

        $this->assertTrue($this->batchArchiver->addDocument($documentOne));

        // document 2
        $pathTwo = 'path-two';
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getPath')->andReturn($pathTwo);

        $documentTwo = Mockery::mock(Document::class);
        $documentTwo->expects('getFileInfo')->andReturn($fileInfo);

        $this->streamFactory
            ->expects('createReadOnlyStream')
            ->with(self::DOCUMENT_BUCKET, $pathTwo)
            ->andReturn($documentResource);

        $this->filenameGenerator
            ->expects('getFileName')
            ->with($documentTwo)
            ->andReturn($filename);

        $this->zipStream
            ->expects('addFileFromPsr7Stream')
            ->with($filename, $documentResource);

        $documentResource->expects('close');

        $this->assertTrue($this->batchArchiver->addDocument($documentTwo));

        // document 3
        $pathThree = 'path-three';
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getPath')->andReturn($pathThree);

        $documentThree = Mockery::mock(Document::class);
        $documentThree->expects('getFileInfo')->andReturn($fileInfo);

        $this->streamFactory
            ->expects('createReadOnlyStream')
            ->with(self::DOCUMENT_BUCKET, $pathThree)
            ->andReturn($documentResource);

        $this->filenameGenerator
            ->expects('getFileName')
            ->with($documentThree)
            ->andReturn($filename);

        $this->zipStream
            ->expects('addFileFromPsr7Stream')
            ->with($filename, $documentResource);

        $documentResource->expects('close');

        $this->assertTrue($this->batchArchiver->addDocument($documentThree));

        $this->zipStream
            ->expects('finish')
            ->andReturn($fileSize = 123);

        $this->zipFile
            ->expects('close');

        $result = $this->batchArchiver->finish();

        self::assertNotFalse($result);
        self::assertEquals(self::BATCH_FILE_NAME, $result->filename);
        self::assertEquals($fileSize, $result->size);
        self::assertEquals(3, $result->fileCount);
    }

    public function testDocumentCountIsResetOnStartOfANewArchive(): void
    {
        // First run
        $batchDownload = Mockery::mock(BatchDownload::class);

        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);

        $this->s3Client->expects('registerStreamWrapperV2');
        $this->streamFactory
            ->expects('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->expects('create')
            ->with($this->zipFile)
            ->andReturn($this->zipStream);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);

        $documentResource = Mockery::mock(StreamInterface::class);
        $filename = 'filename';

        // document 1
        $pathOne = 'path-one';
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getPath')->times(2)->andReturn($pathOne);

        $documentOne = Mockery::mock(Document::class);
        $documentOne->expects('getFileInfo')->times(2)->andReturn($fileInfo);

        $this->streamFactory
            ->expects('createReadOnlyStream')
            ->with(self::DOCUMENT_BUCKET, $pathOne)
            ->andReturn($documentResource);

        $this->filenameGenerator
            ->expects('getFileName')
            ->times(2)
            ->with($documentOne)
            ->andReturn($filename);

        $this->zipStream
            ->expects('addFileFromPsr7Stream')
            ->with($filename, $documentResource);

        $documentResource->expects('close');

        $this->assertTrue($this->batchArchiver->addDocument($documentOne));

        // document 2
        $pathTwo = 'path-two';
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getPath')->andReturn($pathTwo);

        $documentTwo = Mockery::mock(Document::class);
        $documentTwo->expects('getFileInfo')->andReturn($fileInfo);

        $this->streamFactory
            ->expects('createReadOnlyStream')
            ->with(self::DOCUMENT_BUCKET, $pathTwo)
            ->andReturn($documentResource);

        $this->filenameGenerator
            ->expects('getFileName')
            ->with($documentTwo)
            ->andReturn($filename);

        $this->zipStream
            ->expects('addFileFromPsr7Stream')
            ->with($filename, $documentResource);

        $documentResource->expects('close');

        $this->assertTrue($this->batchArchiver->addDocument($documentTwo));

        // document 3
        $pathThree = 'path-three';
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getPath')->andReturn($pathThree);

        $documentThree = Mockery::mock(Document::class);
        $documentThree->expects('getFileInfo')->andReturn($fileInfo);

        $this->streamFactory
            ->expects('createReadOnlyStream')
            ->with(self::DOCUMENT_BUCKET, $pathThree)
            ->andReturn($documentResource);

        $this->filenameGenerator
            ->expects('getFileName')
            ->with($documentThree)
            ->andReturn($filename);

        $this->zipStream
            ->expects('addFileFromPsr7Stream')
            ->with($filename, $documentResource);

        $documentResource->expects('close');

        $this->assertTrue($this->batchArchiver->addDocument($documentThree));

        $this->zipStream
            ->expects('finish')
            ->andReturn($fileSize = 123);

        $this->zipFile
            ->expects('close');

        $result = $this->batchArchiver->finish();

        self::assertNotFalse($result);
        self::assertEquals(self::BATCH_FILE_NAME, $result->filename);
        self::assertEquals($fileSize, $result->size);
        self::assertEquals(3, $result->fileCount);

        // Second run
        $batchDownload = Mockery::mock(BatchDownload::class);

        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);

        $this->s3Client->expects('registerStreamWrapperV2');
        $this->streamFactory
            ->expects('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->expects('create')
            ->with($this->zipFile)
            ->andReturn($this->zipStream);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);

        $documentResource = Mockery::mock(StreamInterface::class);

        $this->streamFactory
            ->expects('createReadOnlyStream')
            ->with(self::DOCUMENT_BUCKET, $pathOne)

            ->andReturn($documentResource);

        $this->zipStream
            ->expects('addFileFromPsr7Stream')
            ->with($filename, $documentResource);

        $documentResource->expects('close');

        $this->assertTrue($this->batchArchiver->addDocument($documentOne));

        $this->zipStream
            ->expects('finish')
            ->andReturn($fileSize = 123);

        $this->zipFile
            ->expects('close');

        $result = $this->batchArchiver->finish();

        self::assertNotFalse($result);
        self::assertEquals(self::BATCH_FILE_NAME, $result->filename);
        self::assertEquals($fileSize, $result->size);
        self::assertEquals(1, $result->fileCount);
    }

    public function testFinishWithFailure(): void
    {
        $batchDownload = Mockery::mock(BatchDownload::class);
        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);

        $this->s3Client->expects('registerStreamWrapperV2');
        $this->streamFactory
            ->expects('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->expects('create')
            ->with($this->zipFile)
            ->andReturn($this->zipStream);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);

        $this->zipStream
            ->expects('finish')
            ->times(2)
            ->andThrow($ex = new FileNotReadableException('my path'));

        $this->logger
            ->expects('error')
            ->with('"ZipStream\Exception\FileNotReadableException" exception thrown', [
                'exceptionMessage' => $ex->getMessage(),
            ]);

        $this->zipFile
            ->expects('close')
            ->times(2);

        $this->s3Client
            ->expects('deleteObject')
            ->with(['Bucket' => self::BATCH_BUCKET, 'Key' => self::BATCH_FILE_NAME]);

        self::assertFalse($this->batchArchiver->finish());
    }

    public function testCleanup(): void
    {
        $batchDownload = Mockery::mock(BatchDownload::class);

        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);

        $this->s3Client->expects('registerStreamWrapperV2');
        $this->streamFactory
            ->expects('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->expects('create')
            ->with($this->zipFile)
            ->andReturn($this->zipStream);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);

        $this->zipStream->expects('finish')->andReturn(0);
        $this->zipFile->expects('close');
        $this->s3Client
            ->expects('deleteObject')
            ->with(['Bucket' => self::BATCH_BUCKET, 'Key' => self::BATCH_FILE_NAME]);

        self::assertTrue($this->batchArchiver->cleanup());
    }
}
