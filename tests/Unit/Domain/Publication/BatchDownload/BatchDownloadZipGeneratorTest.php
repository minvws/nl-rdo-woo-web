<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\BatchDownload;

use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\BatchDownload\Archiver\ArchiveNamer;
use Shared\Domain\Publication\BatchDownload\Archiver\BatchArchiver;
use Shared\Domain\Publication\BatchDownload\Archiver\BatchArchiverResult;
use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\BatchDownload\BatchDownloadStatus;
use Shared\Domain\Publication\BatchDownload\BatchDownloadZipGenerator;
use Shared\Domain\Publication\BatchDownload\Type\BatchDownloadTypeInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

use function array_map;
use function range;

final class BatchDownloadZipGeneratorTest extends UnitTestCase
{
    private WooDecision&MockInterface $wooDecision;
    private Inquiry&MockInterface $inquiry;
    private EntityManagerInterface&MockInterface $doctrine;
    private LoggerInterface&MockInterface $logger;
    private BatchDownloadService&MockInterface $batchDownloadService;
    private BatchArchiver&MockInterface $batchArchiver;
    private ArchiveNamer&MockInterface $archiveNamer;
    private string $fileBaseName = '122';
    private string $archiveName = 'archive.zip';

    private BatchDownloadTypeInterface&MockInterface $batchDownloadType;
    private BatchDownload&MockInterface $batchDownload;

    private BatchDownloadZipGenerator $zipGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wooDecision = Mockery::mock(WooDecision::class);
        $this->inquiry = Mockery::mock(Inquiry::class);
        $this->doctrine = Mockery::mock(EntityManagerInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->batchDownloadService = Mockery::mock(BatchDownloadService::class);
        $this->batchArchiver = Mockery::mock(BatchArchiver::class);
        $this->archiveNamer = Mockery::mock(ArchiveNamer::class);

        $this->batchDownloadType = Mockery::mock(BatchDownloadTypeInterface::class);
        $this->batchDownload = Mockery::mock(BatchDownload::class);

        $this->batchDownloadService
            ->shouldReceive('getType')
            ->with(Mockery::on(function (BatchDownloadScope $scope): bool {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->batchDownloadType);

        $this->batchDownloadType
            ->shouldReceive('getFileBaseName')
            ->with(Mockery::on(function (BatchDownloadScope $scope) {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->fileBaseName);

        $this->archiveNamer
            ->shouldReceive('getArchiveName')
            ->with($this->fileBaseName, $this->batchDownload)
            ->andReturn($this->archiveName);

        $this->batchDownload->shouldReceive('getDossier')->andReturn($this->wooDecision);
        $this->batchDownload->shouldReceive('getInquiry')->andReturn($this->inquiry);
        $this->batchDownload->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->zipGenerator = new BatchDownloadZipGenerator(
            $this->doctrine,
            $this->logger,
            $this->batchDownloadService,
            $this->batchArchiver,
            $this->archiveNamer,
        );
    }

    public function testGenerateArchive(): void
    {
        $this->batchDownloadType->shouldReceive('isAvailableForBatchDownload')->andReturnTrue();

        $documentOne = Mockery::mock(Document::class);
        $documentTwo = Mockery::mock(Document::class);

        $this->batchDownloadType
            ->shouldReceive('getDocumentsQuery->getQuery->getResult')
            ->andReturn([$documentOne, $documentTwo]);

        $this->batchArchiver
            ->shouldReceive('start')->with($this->batchDownloadType, $this->batchDownload, $this->archiveName)
            ->once();

        $this->batchDownload->shouldReceive('setFilename')->with($this->archiveName)->once();
        $this->doctrine->shouldReceive('persist')->with($this->batchDownload)->once();
        $this->doctrine->shouldReceive('flush')->once();

        $this->batchDownloadService->shouldReceive('exists')->with($this->batchDownload)->twice()->andReturnTrue();

        $this->batchDownload->shouldReceive('getStatus')->andReturn(BatchDownloadStatus::PENDING);
        $this->doctrine->shouldReceive('refresh')->with($this->batchDownload)->twice();

        $this->batchArchiver->shouldReceive('addDocument')->with($documentOne)->once()->andReturnTrue();
        $this->batchArchiver->shouldReceive('addDocument')->with($documentTwo)->once()->andReturnTrue();

        $batchArchiverResult = new BatchArchiverResult(
            filename: 'archive.zip',
            size: 12345,
            fileCount: 2,
        );

        $this->batchArchiver->shouldReceive('finish')->once()->andReturn($batchArchiverResult);

        $this->batchDownload
            ->shouldReceive('complete')
            ->with(
                $batchArchiverResult->filename,
                $batchArchiverResult->size,
                $batchArchiverResult->fileCount,
            )
            ->once();
        $this->doctrine->shouldReceive('persist')->with($this->batchDownload)->once();
        $this->doctrine->shouldReceive('flush')->once();

        $this->assertTrue($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveFailsIfNotAvailableForDownload(): void
    {
        $this->batchDownloadType->shouldReceive('isAvailableForBatchDownload')->andReturnFalse();

        $this->expectToFail($this->batchDownload);

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveFailsIfNoDocumentsAreFound(): void
    {
        $this->batchDownloadType->shouldReceive('isAvailableForBatchDownload')->andReturnTrue();

        $this->batchDownloadType->shouldReceive('getDocumentsQuery->getQuery->getResult')->andReturn([]);

        $this->expectToFail($this->batchDownload);

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveFailsIfBatchDownloadHasBeenDeleted(): void
    {
        $this->batchDownloadType->shouldReceive('isAvailableForBatchDownload')->andReturnTrue();

        $documentOne = Mockery::mock(Document::class);
        $documentTwo = Mockery::mock(Document::class);

        $this->batchDownloadType
            ->shouldReceive('getDocumentsQuery->getQuery->getResult')
            ->andReturn([$documentOne, $documentTwo]);

        $this->batchArchiver
            ->shouldReceive('start')
            ->with($this->batchDownloadType, $this->batchDownload, $this->archiveName)->once();

        $this->batchDownload->shouldReceive('setFilename')->with($this->archiveName)->once();
        $this->doctrine->shouldReceive('persist')->with($this->batchDownload)->once();
        $this->doctrine->shouldReceive('flush')->once();

        $this->batchDownloadService->shouldReceive('exists')->with($this->batchDownload)->once()->andReturnTrue();
        $this->batchDownloadService->shouldReceive('exists')->with($this->batchDownload)->once()->andReturnFalse();

        $this->batchDownload->shouldReceive('getStatus')->andReturn(BatchDownloadStatus::PENDING);
        $this->doctrine->shouldReceive('refresh')->with($this->batchDownload)->once();

        $this->batchArchiver->shouldReceive('addDocument')->with($documentOne)->once()->andReturnTrue();

        $this->logger->shouldReceive('info')->with('Batch download has been deleted, stopping processing', [
            'batch_id' => $this->batchDownload->getId()->toRfc4122(),
        ]);

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveFailsIfBatchIsNoLongerPending(): void
    {
        $this->batchDownloadType->shouldReceive('isAvailableForBatchDownload')->andReturnTrue();

        $documentOne = Mockery::mock(Document::class);
        $documentOne->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->batchDownloadType->shouldReceive('getDocumentsQuery->getQuery->getResult')->andReturn([$documentOne]);

        $this->batchArchiver
            ->shouldReceive('start')
            ->with($this->batchDownloadType, $this->batchDownload, $this->archiveName)
            ->once();

        $this->batchDownload->shouldReceive('setFilename')->with($this->archiveName)->once();
        $this->doctrine->shouldReceive('persist')->with($this->batchDownload)->once();
        $this->doctrine->shouldReceive('flush')->once();

        $this->batchDownloadService->shouldReceive('exists')->with($this->batchDownload)->once()->andReturnTrue();

        $this->batchDownload->shouldReceive('getStatus')->andReturn(BatchDownloadStatus::OUTDATED);
        $this->doctrine->shouldReceive('refresh')->with($this->batchDownload)->once();

        $this->logger
            ->shouldReceive('info')
            ->with(
                'Batch download status is no longer pending, stopping batch archive generation',
                ['batch_id' => $this->batchDownload->getId()->toRfc4122()],
            );

        $this->batchArchiver->shouldReceive('cleanup')->once();

        $this->batchArchiver->shouldNotReceive('addDocument')->with($documentOne);

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveOnlyChecksIsNoLongerPendingEveryXDocuments(): void
    {
        $this->batchDownloadType->shouldReceive('isAvailableForBatchDownload')->andReturnTrue();

        $numberOfDocuments = 50;
        $documents = array_map(function () {
            $document = Mockery::mock(Document::class);
            $document->shouldReceive('getId')->andReturn(Uuid::v6());

            return $document;
        }, range(1, $numberOfDocuments));

        $this->batchDownloadType->shouldReceive('getDocumentsQuery->getQuery->getResult')->andReturn($documents);

        $this->batchArchiver
            ->shouldReceive('start')
            ->with($this->batchDownloadType, $this->batchDownload, $this->archiveName)
            ->once();

        $this->batchDownload->shouldReceive('setFilename')->with($this->archiveName)->once();
        $this->doctrine->shouldReceive('persist')->with($this->batchDownload)->once();
        $this->doctrine->shouldReceive('flush')->once();

        $this->batchDownloadService
            ->shouldReceive('exists')
            ->with($this->batchDownload)
            ->times($numberOfDocuments)
            ->andReturnTrue();

        $this->batchDownload->shouldReceive('getStatus')->andReturn(BatchDownloadStatus::PENDING);
        $this->doctrine->shouldReceive('refresh')->with($this->batchDownload)->times(6);

        $this->batchArchiver->shouldReceive('addDocument')->times($numberOfDocuments)->andReturnTrue();

        $batchArchiverResult = new BatchArchiverResult(
            filename: 'archive.zip',
            size: 12345,
            fileCount: $numberOfDocuments,
        );

        $this->batchArchiver->shouldReceive('finish')->once()->andReturn($batchArchiverResult);

        $this->batchDownload
            ->shouldReceive('complete')
            ->with(
                $batchArchiverResult->filename,
                $batchArchiverResult->size,
                $batchArchiverResult->fileCount,
            )
            ->once();
        $this->doctrine->shouldReceive('persist')->with($this->batchDownload)->once();
        $this->doctrine->shouldReceive('flush')->once();

        $this->assertTrue($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveFailsIfItFailedToAddDocument(): void
    {
        $this->batchDownloadType->shouldReceive('isAvailableForBatchDownload')->andReturnTrue();

        $documentOne = Mockery::mock(Document::class);
        $documentTwo = Mockery::mock(Document::class);
        $documentTwo->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->batchDownloadType
            ->shouldReceive('getDocumentsQuery->getQuery->getResult')
            ->andReturn([$documentOne, $documentTwo]);

        $this->batchArchiver
            ->shouldReceive('start')
            ->with($this->batchDownloadType, $this->batchDownload, $this->archiveName)
            ->once();

        $this->batchDownload->shouldReceive('setFilename')->with($this->archiveName)->once();
        $this->doctrine->shouldReceive('persist')->with($this->batchDownload)->once();
        $this->doctrine->shouldReceive('flush')->once();

        $this->batchDownloadService->shouldReceive('exists')->with($this->batchDownload)->twice()->andReturnTrue();

        $this->batchDownload->shouldReceive('getStatus')->andReturn(BatchDownloadStatus::PENDING);
        $this->doctrine->shouldReceive('refresh')->with($this->batchDownload)->once();

        $this->batchArchiver->shouldReceive('addDocument')->with($documentOne)->once()->andReturnTrue();
        $this->batchArchiver->shouldReceive('addDocument')->with($documentTwo)->once()->andReturnFalse();

        $this->logger->shouldReceive('error')->with('Could not add document to archive', [
            'batch_id' => $this->batchDownload->getId()->toRfc4122(),
            'document_id' => $documentTwo->getId()->toRfc4122(),
        ]);

        $this->expectToFail($this->batchDownload);

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveFailsFinishingArchive(): void
    {
        $this->batchDownloadType->shouldReceive('isAvailableForBatchDownload')->andReturnTrue();

        $document = Mockery::mock(Document::class);

        $this->batchDownloadType->shouldReceive('getDocumentsQuery->getQuery->getResult')->andReturn([$document]);

        $this->batchArchiver
            ->shouldReceive('start')
            ->with($this->batchDownloadType, $this->batchDownload, $this->archiveName)
            ->once();

        $this->batchDownload->shouldReceive('setFilename')->with($this->archiveName)->once();
        $this->doctrine->shouldReceive('persist')->with($this->batchDownload)->once();
        $this->doctrine->shouldReceive('flush')->once();

        $this->batchDownloadService->shouldReceive('exists')->with($this->batchDownload)->once()->andReturnTrue();

        $this->batchDownload->shouldReceive('getStatus')->andReturn(BatchDownloadStatus::PENDING);
        $this->doctrine->shouldReceive('refresh')->with($this->batchDownload)->once();

        $this->batchArchiver->shouldReceive('addDocument')->with($document)->once()->andReturnTrue();

        $this->batchArchiver->shouldReceive('finish')->once()->andReturnFalse();

        $this->expectToFail($this->batchDownload);

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveCallsCleanupWhenBatchNoLongerPendingWhenFinishing(): void
    {
        $this->batchDownloadType->shouldReceive('isAvailableForBatchDownload')->andReturnTrue();

        $documentOne = Mockery::mock(Document::class);
        $documentTwo = Mockery::mock(Document::class);

        $this->batchDownloadType
            ->shouldReceive('getDocumentsQuery->getQuery->getResult')
            ->andReturn([$documentOne, $documentTwo]);

        $this->batchArchiver
            ->shouldReceive('start')
            ->with($this->batchDownloadType, $this->batchDownload, $this->archiveName)
            ->once();

        $this->batchDownload->shouldReceive('setFilename')->with($this->archiveName)->once();
        $this->doctrine->shouldReceive('persist')->with($this->batchDownload)->once();
        $this->doctrine->shouldReceive('flush')->once();

        $this->batchDownloadService->shouldReceive('exists')->with($this->batchDownload)->twice()->andReturnTrue();

        $this->batchDownload->shouldReceive('getStatus')->once()->andReturn(BatchDownloadStatus::PENDING);
        $this->batchDownload->shouldReceive('getStatus')->once()->andReturn(BatchDownloadStatus::OUTDATED);
        $this->doctrine->shouldReceive('refresh')->with($this->batchDownload)->twice();

        $this->batchArchiver->shouldReceive('addDocument')->with($documentOne)->once()->andReturnTrue();
        $this->batchArchiver->shouldReceive('addDocument')->with($documentTwo)->once()->andReturnTrue();

        $batchArchiverResult = new BatchArchiverResult(
            filename: 'archive.zip',
            size: 12345,
            fileCount: 2,
        );

        $this->batchArchiver->shouldReceive('finish')->once()->andReturn($batchArchiverResult);

        $this->batchArchiver->shouldReceive('cleanup')->once();

        $this->batchDownload->shouldNotReceive('complete');

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    private function expectToFail(BatchDownload&MockInterface $batchDownload): void
    {
        $batchDownload->shouldReceive('markAsFailed')->once();
        $this->doctrine->shouldReceive('persist')->with($batchDownload)->once();
        $this->doctrine->shouldReceive('flush')->once();
    }
}
