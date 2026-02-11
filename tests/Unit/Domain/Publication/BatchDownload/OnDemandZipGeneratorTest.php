<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\BatchDownload;

use Aws\S3\S3Client;
use Doctrine\ORM\QueryBuilder;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\StreamInterface;
use Shared\Domain\Publication\BatchDownload\Archiver\ArchiveNamer;
use Shared\Domain\Publication\BatchDownload\Archiver\ZipStreamFactory;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\BatchDownload\OnDemandZipGenerator;
use Shared\Domain\Publication\BatchDownload\Type\BatchDownloadTypeInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\S3\StreamFactory;
use Shared\Service\DownloadFilenameGenerator;
use Shared\Tests\Unit\UnitTestCase;
use ZipStream\ZipStream;

use function ob_end_clean;
use function ob_start;

class OnDemandZipGeneratorTest extends UnitTestCase
{
    private BatchDownloadService&MockInterface $batchDownloadService;
    private StreamFactory&MockInterface $streamFactory;
    private ZipStreamFactory&MockInterface $zipStreamFactory;
    private ArchiveNamer&MockInterface $archiveNamer;
    private DownloadFilenameGenerator&MockInterface $filenameGenerator;
    private S3Client&MockInterface $s3Client;
    private string $bucket = 'test-bucket';
    private OnDemandZipGenerator $zipGenerator;

    protected function setUp(): void
    {
        $this->batchDownloadService = Mockery::mock(BatchDownloadService::class);
        $this->streamFactory = Mockery::mock(StreamFactory::class);
        $this->zipStreamFactory = Mockery::mock(ZipStreamFactory::class);
        $this->archiveNamer = Mockery::mock(ArchiveNamer::class);
        $this->filenameGenerator = Mockery::mock(DownloadFilenameGenerator::class);
        $this->s3Client = Mockery::mock(S3Client::class);

        $this->zipGenerator = new OnDemandZipGenerator(
            $this->batchDownloadService,
            $this->streamFactory,
            $this->zipStreamFactory,
            $this->archiveNamer,
            $this->filenameGenerator,
            $this->bucket,
            $this->s3Client,
        );
    }

    public function testGetStreamedResponse(): void
    {
        $scope = Mockery::mock(BatchDownloadScope::class);
        $type = Mockery::mock(BatchDownloadTypeInterface::class);
        $this->batchDownloadService->expects('getType')->with($scope)->andReturn($type);

        $documentOne = Mockery::mock(Document::class);
        $documentOne->shouldReceive('getFileInfo->getPath')->andReturn($path1 = '/path/to/file/1');
        $documentOneStream = Mockery::mock(StreamInterface::class);

        $documentTwo = Mockery::mock(Document::class);
        $documentTwo->shouldReceive('getFileInfo->getPath')->andReturn($path2 = '/path/to/file/2');
        $documentTwoStream = Mockery::mock(StreamInterface::class);

        $type->shouldReceive('getDocumentsQuery->getQuery->getResult')->andReturn([$documentOne, $documentTwo]);
        $type->shouldReceive('getFileBaseName')->with($scope)->andReturn($baseName = 'foo-bar');

        $zip = Mockery::mock(ZipStream::class);
        $this->s3Client->expects('registerStreamWrapperV2');
        $this->archiveNamer->expects('getArchiveNameForStream')->with($baseName)->andReturn($zipName = 'foo-bar.zip');
        $this->zipStreamFactory->expects('forStreamingArchive')->with($zipName)->andReturn($zip);
        $zip->expects('addDirectory')->with($baseName);

        $this->streamFactory->expects('createReadOnlyStream')->with($this->bucket, $path1)->andReturn($documentOneStream);
        $this->streamFactory->expects('createReadOnlyStream')->with($this->bucket, $path2)->andReturn($documentTwoStream);

        $this->filenameGenerator->expects('getFileName')->with($documentOne)->andReturn('1.pdf');
        $this->filenameGenerator->expects('getFileName')->with($documentTwo)->andReturn('2.pdf');

        $zip->expects('addFileFromPsr7Stream')->with('foo-bar/1.pdf', $documentOneStream);
        $zip->expects('addFileFromPsr7Stream')->with('foo-bar/2.pdf', $documentTwoStream);

        $zip->expects('finish');

        $response = $this->zipGenerator->getStreamedResponse($scope);

        $this->assertStringContainsStringIgnoringCase('no-store', $response->headers->get('Cache-Control', ''));

        ob_start();
        $response->sendContent();
        ob_end_clean();
    }

    public function testGetDetails(): void
    {
        $scope = Mockery::mock(BatchDownloadScope::class);

        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $queryBuilder->expects('select');
        $queryBuilder->expects('getQuery->getSingleResult')
            ->andReturn(['doc_count' => $docCount = 123, 'total_size' => $totalSize = 456]);

        $type = Mockery::mock(BatchDownloadTypeInterface::class);
        $type->shouldReceive('getFileBaseName')->with($scope)->andReturn($baseName = 'foo-bar');
        $type->shouldReceive('getDocumentsQuery')->andReturn($queryBuilder);

        $this->batchDownloadService->expects('getType')->with($scope)->andReturn($type);

        $this->archiveNamer->expects('getArchiveNameForStream')->with($baseName)->andReturn($zipName = 'foo-bar.zip');

        $details = $this->zipGenerator->getDetails($scope);

        self::assertEquals($zipName, $details->name);
        self::assertEquals($docCount, $details->documentCount);
        self::assertEquals($totalSize, $details->totalDocumentSize);
    }
}
