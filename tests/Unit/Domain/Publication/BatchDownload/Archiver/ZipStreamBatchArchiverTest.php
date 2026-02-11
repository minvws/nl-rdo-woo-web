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
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\Type\BatchDownloadTypeInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
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
        $this->startArchiver();
    }

    public function testStartWithExceptionThrown(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $inquiry = Mockery::mock(Inquiry::class);

        $batchDownload = Mockery::mock(BatchDownload::class);
        $batchDownload->shouldReceive('getDossier')->andReturn($wooDecision);
        $batchDownload->shouldReceive('getInquiry')->andReturn($inquiry);

        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);
        $downloadType
            ->shouldReceive('getFileBaseName')
            ->with(Mockery::on(
                static function (BatchDownloadScope $scope) use ($wooDecision, $inquiry): bool {
                    return $scope->wooDecision === $wooDecision && $scope->inquiry === $inquiry;
                }
            ))
            ->andReturn('file-base-name');

        $this->s3Client->shouldReceive('registerStreamWrapperV2')->once();
        $this->streamFactory
            ->shouldReceive('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->shouldReceive('create')
            ->with($this->zipFile)
            ->andThrow($ex = new FileNotReadableException('my path'));

        $this->logger
            ->shouldReceive('error')
            ->with('"ZipStream\Exception\FileNotReadableException" exception thrown', [
                'exceptionMessage' => $ex->getMessage(),
            ])
            ->once();

        $this->zipFile
            ->shouldReceive('close')
            ->once();

        $this->s3Client
            ->shouldReceive('deleteObject')->with(['Bucket' => self::BATCH_BUCKET, 'Key' => self::BATCH_FILE_NAME])
            ->once();

        $this->expectExceptionObject($ex);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);
    }

    public function testStartWithExceptionThrownAndFailingToDeleteS3Object(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $inquiry = Mockery::mock(Inquiry::class);

        $batchDownload = Mockery::mock(BatchDownload::class);
        $batchDownload->shouldReceive('getDossier')->andReturn($wooDecision);
        $batchDownload->shouldReceive('getInquiry')->andReturn($inquiry);

        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);
        $downloadType
            ->shouldReceive('getFileBaseName')
            ->with(Mockery::on(
                static function (BatchDownloadScope $scope) use ($wooDecision, $inquiry): bool {
                    return $scope->wooDecision === $wooDecision && $scope->inquiry === $inquiry;
                }
            ))
            ->andReturn('file-base-name');

        $this->s3Client->shouldReceive('registerStreamWrapperV2')->once();
        $this->streamFactory
            ->shouldReceive('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->shouldReceive('create')
            ->with($this->zipFile)
            ->andThrow($ex1 = new FileNotReadableException('my path'));

        $this->logger
            ->shouldReceive('error')
            ->with('"ZipStream\Exception\FileNotReadableException" exception thrown', [
                'exceptionMessage' => $ex1->getMessage(),
            ])
            ->once();

        $this->zipFile
            ->shouldReceive('close')
            ->once();

        $this->s3Client
            ->shouldReceive('deleteObject')
            ->with(['Bucket' => self::BATCH_BUCKET, 'Key' => self::BATCH_FILE_NAME])
            ->once()
            ->andThrow($ex2 = new S3Exception('failed to delete', Mockery::mock(CommandInterface::class)));

        $this->logger
            ->shouldReceive('error')
            ->with('"Aws\S3\Exception\S3Exception" exception thrown', [
                'exceptionMessage' => $ex2->getMessage(),
            ])
            ->once();

        $this->expectExceptionObject($ex1);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);
    }

    public function testAddDocument(): void
    {
        $this->startArchiver();

        $document = $this->createDocument($path = 'path');

        $this->addDocument($document, $path);
    }

    public function testAddDocumentWithFailure(): void
    {
        $this->startArchiver();

        $document = $this->createDocument($path = 'path');

        $documentResource = Mockery::mock(StreamInterface::class);

        $this->streamFactory
            ->shouldReceive('createReadOnlyStream')
            ->with(self::DOCUMENT_BUCKET, $path)
            ->once()
            ->andReturn($documentResource);

        $this->filenameGenerator
            ->shouldReceive('getFileName')
            ->with($document)
            ->andReturn($filename = 'filename');

        $this->zipStream
            ->shouldReceive('addFileFromPsr7Stream')
            ->with($filename, $documentResource)
            ->once()
            ->andThrow($ex = new FileNotReadableException('my path'));

        $this->logger
            ->shouldReceive('error')
            ->with('"ZipStream\Exception\FileNotReadableException" exception thrown', [
                'exceptionMessage' => $ex->getMessage(),
            ])
            ->once();

        $this->zipStream->shouldReceive('finish')->once()->andReturn(0);

        $this->zipFile
            ->shouldReceive('close')
            ->once();

        $this->s3Client
            ->shouldReceive('deleteObject')
            ->with(['Bucket' => self::BATCH_BUCKET, 'Key' => self::BATCH_FILE_NAME])
            ->once();

        $documentResource->shouldReceive('close')->once();

        $this->assertFalse($this->batchArchiver->addDocument($document));
    }

    public function testFinish(): void
    {
        $this->startArchiver();

        $this->zipStream
            ->shouldReceive('finish')
            ->andReturn($fileSize = 123);

        $this->zipFile
            ->shouldReceive('close')
            ->once();

        $result = $this->batchArchiver->finish();

        self::assertNotFalse($result);
        self::assertEquals(self::BATCH_FILE_NAME, $result->filename);
        self::assertEquals($fileSize, $result->size);
        self::assertEquals(0, $result->fileCount);
    }

    public function testFinishWithMultipleDocumentsAdded(): void
    {
        $this->startArchiver();

        $documentOne = $this->createDocument($pathOne = 'path-one');
        $this->addDocument($documentOne, $pathOne);

        $documentTwo = $this->createDocument($pathTwo = 'path-two');
        $this->addDocument($documentTwo, $pathTwo);

        $documentThree = $this->createDocument($pathThree = 'path-three');
        $this->addDocument($documentThree, $pathThree);

        $this->zipStream
            ->shouldReceive('finish')
            ->once()
            ->andReturn($fileSize = 123);

        $this->zipFile
            ->shouldReceive('close')
            ->once();

        $result = $this->batchArchiver->finish();

        self::assertNotFalse($result);
        self::assertEquals(self::BATCH_FILE_NAME, $result->filename);
        self::assertEquals($fileSize, $result->size);
        self::assertEquals(3, $result->fileCount);
    }

    public function testDocumentCountIsResetOnStartOfANewArchive(): void
    {
        // First run
        $this->startArchiver();

        $documentOne = $this->createDocument($pathOne = 'path-one');
        $this->addDocument($documentOne, $pathOne);

        $documentTwo = $this->createDocument($pathTwo = 'path-two');
        $this->addDocument($documentTwo, $pathTwo);

        $documentThree = $this->createDocument($pathThree = 'path-three');
        $this->addDocument($documentThree, $pathThree);

        $this->zipStream
            ->shouldReceive('finish')
            ->once()
            ->andReturn($fileSize = 123);

        $this->zipFile
            ->shouldReceive('close')
            ->once();

        $result = $this->batchArchiver->finish();

        self::assertNotFalse($result);
        self::assertEquals(self::BATCH_FILE_NAME, $result->filename);
        self::assertEquals($fileSize, $result->size);
        self::assertEquals(3, $result->fileCount);

        // Second run
        $this->startArchiver();
        $this->addDocument($documentOne, $pathOne);

        $this->zipStream
            ->shouldReceive('finish')
            ->once()
            ->andReturn($fileSize = 123);

        $this->zipFile
            ->shouldReceive('close')
            ->once();

        $result = $this->batchArchiver->finish();

        self::assertNotFalse($result);
        self::assertEquals(self::BATCH_FILE_NAME, $result->filename);
        self::assertEquals($fileSize, $result->size);
        self::assertEquals(1, $result->fileCount);
    }

    public function testFinishWithFailure(): void
    {
        $this->startArchiver();

        $this->zipStream
            ->shouldReceive('finish')
            ->andThrow($ex = new FileNotReadableException('my path'));

        $this->logger
            ->shouldReceive('error')
            ->with('"ZipStream\Exception\FileNotReadableException" exception thrown', [
                'exceptionMessage' => $ex->getMessage(),
            ])
            ->once();

        $this->zipFile
            ->shouldReceive('close')
            ->twice();

        $this->s3Client
            ->shouldReceive('deleteObject')
            ->with(['Bucket' => self::BATCH_BUCKET, 'Key' => self::BATCH_FILE_NAME])
            ->once();

        self::assertFalse($this->batchArchiver->finish());
    }

    public function testCleanup(): void
    {
        $this->startArchiver();

        $this->zipStream->shouldReceive('finish')->once()->andReturn(0);
        $this->zipFile->shouldReceive('close')->once();
        $this->s3Client
            ->shouldReceive('deleteObject')
            ->with(['Bucket' => self::BATCH_BUCKET, 'Key' => self::BATCH_FILE_NAME])
            ->once();

        self::assertTrue($this->batchArchiver->cleanup());
    }

    private function startArchiver(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $inquiry = Mockery::mock(Inquiry::class);

        $batchDownload = Mockery::mock(BatchDownload::class);
        $batchDownload->shouldReceive('getDossier')->andReturn($wooDecision);
        $batchDownload->shouldReceive('getInquiry')->andReturn($inquiry);

        $downloadType = Mockery::mock(BatchDownloadTypeInterface::class);
        $downloadType
            ->shouldReceive('getFileBaseName')
            ->with(Mockery::on(
                function (BatchDownloadScope $scope) use ($wooDecision, $inquiry): bool {
                    return $scope->wooDecision === $wooDecision && $scope->inquiry === $inquiry;
                }
            ))
            ->andReturn($basename = 'file-base-name');

        $this->s3Client->shouldReceive('registerStreamWrapperV2')->once();
        $this->streamFactory
            ->shouldReceive('createWriteOnlyStream')
            ->with(self::BATCH_BUCKET, self::BATCH_FILE_NAME)
            ->andReturn($this->zipFile);

        $this->zipStreamFactory
            ->shouldReceive('create')
            ->with($this->zipFile)
            ->andReturn($this->zipStream);

        $this->batchArchiver->start($downloadType, $batchDownload, self::BATCH_FILE_NAME);
    }

    private function addDocument(Document $document, string $path): void
    {
        $documentResource = Mockery::mock(StreamInterface::class);

        $this->streamFactory
            ->shouldReceive('createReadOnlyStream')
            ->with(self::DOCUMENT_BUCKET, $path)
            ->once()
            ->andReturn($documentResource);

        $this->filenameGenerator
            ->shouldReceive('getFileName')
            ->with($document)
            ->andReturn($filename = 'filename');

        $this->zipStream
            ->shouldReceive('addFileFromPsr7Stream')
            ->with($filename, $documentResource)
            ->once();

        $documentResource->shouldReceive('close')->once();

        $this->assertTrue($this->batchArchiver->addDocument($document));
    }

    private function createDocument(string $path): Document&MockInterface
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getPath')->andReturn($path);

        $document = Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        return $document;
    }
}
