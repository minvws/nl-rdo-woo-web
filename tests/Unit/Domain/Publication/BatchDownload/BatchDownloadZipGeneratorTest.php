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
        $this->batchDownloadService
            ->expects('getType')
            ->with(Mockery::on(function (BatchDownloadScope $scope): bool {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->batchDownloadType);

        $this->batchDownloadType
            ->expects('getFileBaseName')
            ->with(Mockery::on(function (BatchDownloadScope $scope) {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->fileBaseName);

        $this->archiveNamer
            ->expects('getArchiveName')
            ->with($this->fileBaseName, $this->batchDownload)
            ->andReturn($this->archiveName);

        $this->batchDownload->expects('getDossier')->andReturn($this->wooDecision);
        $this->batchDownload->expects('getInquiry')->andReturn($this->inquiry);

        $this->batchDownloadType->expects('isAvailableForBatchDownload')->andReturnTrue();

        $documentOne = Mockery::mock(Document::class);
        $documentTwo = Mockery::mock(Document::class);

        $this->batchDownloadType
            ->expects('getDocumentsQuery->getQuery->getResult')
            ->andReturn([$documentOne, $documentTwo]);

        $this->batchArchiver
            ->expects('start')->with($this->batchDownloadType, $this->batchDownload, $this->archiveName);

        $this->batchDownload->expects('setFilename')->with($this->archiveName);
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->batchDownloadService->expects('exists')->with($this->batchDownload)->times(2)->andReturnTrue();

        $this->batchDownload->expects('getStatus')->times(2)->andReturn(BatchDownloadStatus::PENDING);
        $this->doctrine->expects('refresh')->with($this->batchDownload)->times(2);

        $this->batchArchiver->expects('addDocument')->with($documentOne)->andReturnTrue();
        $this->batchArchiver->expects('addDocument')->with($documentTwo)->andReturnTrue();

        $batchArchiverResult = new BatchArchiverResult(
            filename: 'archive.zip',
            size: 12345,
            fileCount: 2,
        );

        $this->batchArchiver->expects('finish')->andReturn($batchArchiverResult);

        $this->batchDownload
            ->expects('complete')
            ->with(
                $batchArchiverResult->filename,
                $batchArchiverResult->size,
                $batchArchiverResult->fileCount,
            );
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->assertTrue($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveFailsIfNotAvailableForDownload(): void
    {
        $this->batchDownloadService
            ->expects('getType')
            ->with(Mockery::on(function (BatchDownloadScope $scope): bool {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->batchDownloadType);

        $this->batchDownload->expects('getDossier')->andReturn($this->wooDecision);
        $this->batchDownload->expects('getInquiry')->andReturn($this->inquiry);

        $this->batchDownloadType->expects('isAvailableForBatchDownload')->andReturnFalse();

        $this->batchDownload->expects('markAsFailed');
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveFailsIfNoDocumentsAreFound(): void
    {
        $this->batchDownloadService
            ->expects('getType')
            ->with(Mockery::on(function (BatchDownloadScope $scope): bool {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->batchDownloadType);

        $this->batchDownload->expects('getDossier')->andReturn($this->wooDecision);
        $this->batchDownload->expects('getInquiry')->andReturn($this->inquiry);

        $this->batchDownloadType->expects('isAvailableForBatchDownload')->andReturnTrue();

        $this->batchDownloadType->expects('getDocumentsQuery->getQuery->getResult')->andReturn([]);

        $this->batchDownload->expects('markAsFailed');
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveFailsIfBatchDownloadHasBeenDeleted(): void
    {
        $this->batchDownloadService
            ->expects('getType')
            ->with(Mockery::on(function (BatchDownloadScope $scope): bool {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->batchDownloadType);

        $this->batchDownloadType
            ->expects('getFileBaseName')
            ->with(Mockery::on(function (BatchDownloadScope $scope) {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->fileBaseName);

        $this->archiveNamer
            ->expects('getArchiveName')
            ->with($this->fileBaseName, $this->batchDownload)
            ->andReturn($this->archiveName);

        $this->batchDownload->expects('getDossier')->andReturn($this->wooDecision);
        $this->batchDownload->expects('getInquiry')->andReturn($this->inquiry);
        $this->batchDownload->expects('getId')->times(2)->andReturn(Uuid::v6());

        $this->batchDownloadType->expects('isAvailableForBatchDownload')->andReturnTrue();

        $documentOne = Mockery::mock(Document::class);
        $documentTwo = Mockery::mock(Document::class);

        $this->batchDownloadType
            ->expects('getDocumentsQuery->getQuery->getResult')
            ->andReturn([$documentOne, $documentTwo]);

        $this->batchArchiver
            ->expects('start')
            ->with($this->batchDownloadType, $this->batchDownload, $this->archiveName);

        $this->batchDownload->expects('setFilename')->with($this->archiveName);
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->batchDownloadService->expects('exists')->with($this->batchDownload)->andReturnTrue();
        $this->batchDownloadService->expects('exists')->with($this->batchDownload)->andReturnFalse();

        $this->batchDownload->expects('getStatus')->andReturn(BatchDownloadStatus::PENDING);
        $this->doctrine->expects('refresh')->with($this->batchDownload);

        $this->batchArchiver->expects('addDocument')->with($documentOne)->andReturnTrue();

        $this->logger->expects('info')->with('Batch download has been deleted, stopping processing', [
            'batch_id' => $this->batchDownload->getId()->toRfc4122(),
        ]);

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveFailsIfBatchIsNoLongerPending(): void
    {
        $this->batchDownloadService
            ->expects('getType')
            ->with(Mockery::on(function (BatchDownloadScope $scope): bool {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->batchDownloadType);

        $this->batchDownloadType
            ->expects('getFileBaseName')
            ->with(Mockery::on(function (BatchDownloadScope $scope) {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->fileBaseName);

        $this->archiveNamer
            ->expects('getArchiveName')
            ->with($this->fileBaseName, $this->batchDownload)
            ->andReturn($this->archiveName);

        $this->batchDownload->expects('getDossier')->andReturn($this->wooDecision);
        $this->batchDownload->expects('getInquiry')->andReturn($this->inquiry);
        $this->batchDownload->expects('getId')->times(2)->andReturn(Uuid::v6());

        $this->batchDownloadType->expects('isAvailableForBatchDownload')->andReturnTrue();

        $documentOne = Mockery::mock(Document::class);

        $this->batchDownloadType->expects('getDocumentsQuery->getQuery->getResult')->andReturn([$documentOne]);

        $this->batchArchiver
            ->expects('start')
            ->with($this->batchDownloadType, $this->batchDownload, $this->archiveName);

        $this->batchDownload->expects('setFilename')->with($this->archiveName);
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->batchDownloadService->expects('exists')->with($this->batchDownload)->andReturnTrue();

        $this->batchDownload->expects('getStatus')->andReturn(BatchDownloadStatus::OUTDATED);
        $this->doctrine->expects('refresh')->with($this->batchDownload);

        $this->logger
            ->expects('info')
            ->with(
                'Batch download status is no longer pending, stopping batch archive generation',
                ['batch_id' => $this->batchDownload->getId()->toRfc4122()],
            );

        $this->batchArchiver->expects('cleanup');

        $this->batchArchiver->shouldNotReceive('addDocument')->with($documentOne);

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveOnlyChecksIsNoLongerPendingEveryXDocuments(): void
    {
        $this->batchDownload->expects('getDossier')->andReturn($this->wooDecision);
        $this->batchDownload->expects('getInquiry')->andReturn($this->inquiry);

        $this->batchDownloadService
            ->expects('getType')
            ->with(Mockery::on(function (BatchDownloadScope $scope): bool {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->batchDownloadType);

        $this->batchDownloadType
            ->expects('getFileBaseName')
            ->with(Mockery::on(function (BatchDownloadScope $scope) {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->fileBaseName);

        $this->archiveNamer
            ->expects('getArchiveName')
            ->with($this->fileBaseName, $this->batchDownload)
            ->andReturn($this->archiveName);

        $this->batchDownloadType->expects('isAvailableForBatchDownload')->andReturnTrue();

        $numberOfDocuments = 50;
        $documents = array_map(static function () {
            return Mockery::mock(Document::class);
        }, range(1, $numberOfDocuments));

        $this->batchDownloadType->expects('getDocumentsQuery->getQuery->getResult')->andReturn($documents);

        $this->batchArchiver
            ->expects('start')
            ->with($this->batchDownloadType, $this->batchDownload, $this->archiveName);

        $this->batchDownload->expects('setFilename')->with($this->archiveName);
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->batchDownloadService
            ->expects('exists')
            ->with($this->batchDownload)
            ->times($numberOfDocuments)
            ->andReturnTrue();

        $this->batchDownload->expects('getStatus')->times(6)->andReturn(BatchDownloadStatus::PENDING);
        $this->doctrine->expects('refresh')->with($this->batchDownload)->times(6);

        $this->batchArchiver->expects('addDocument')->times($numberOfDocuments)->andReturnTrue();

        $batchArchiverResult = new BatchArchiverResult(
            filename: 'archive.zip',
            size: 12345,
            fileCount: $numberOfDocuments,
        );

        $this->batchArchiver->expects('finish')->andReturn($batchArchiverResult);

        $this->batchDownload
            ->expects('complete')
            ->with(
                $batchArchiverResult->filename,
                $batchArchiverResult->size,
                $batchArchiverResult->fileCount,
            );
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->assertTrue($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveFailsIfItFailedToAddDocument(): void
    {
        $this->batchDownloadService
            ->expects('getType')
            ->with(Mockery::on(function (BatchDownloadScope $scope): bool {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->batchDownloadType);

        $this->batchDownloadType
            ->expects('getFileBaseName')
            ->with(Mockery::on(function (BatchDownloadScope $scope) {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->fileBaseName);

        $this->archiveNamer
            ->expects('getArchiveName')
            ->with($this->fileBaseName, $this->batchDownload)
            ->andReturn($this->archiveName);

        $this->batchDownload->expects('getDossier')->andReturn($this->wooDecision);
        $this->batchDownload->expects('getInquiry')->andReturn($this->inquiry);
        $this->batchDownload->expects('getId')->times(2)->andReturn(Uuid::v6());

        $this->batchDownloadType->expects('isAvailableForBatchDownload')->andReturnTrue();

        $documentOne = Mockery::mock(Document::class);
        $documentTwo = Mockery::mock(Document::class);
        $documentTwo->expects('getId')->times(2)->andReturn(Uuid::v6());

        $this->batchDownloadType
            ->expects('getDocumentsQuery->getQuery->getResult')
            ->andReturn([$documentOne, $documentTwo]);

        $this->batchArchiver
            ->expects('start')
            ->with($this->batchDownloadType, $this->batchDownload, $this->archiveName);

        $this->batchDownload->expects('setFilename')->with($this->archiveName);
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->batchDownloadService->expects('exists')->with($this->batchDownload)->times(2)->andReturnTrue();

        $this->batchDownload->expects('getStatus')->andReturn(BatchDownloadStatus::PENDING);
        $this->doctrine->expects('refresh')->with($this->batchDownload);

        $this->batchArchiver->expects('addDocument')->with($documentOne)->andReturnTrue();
        $this->batchArchiver->expects('addDocument')->with($documentTwo)->andReturnFalse();

        $this->logger->expects('error')->with('Could not add document to archive', [
            'batch_id' => $this->batchDownload->getId()->toRfc4122(),
            'document_id' => $documentTwo->getId()->toRfc4122(),
        ]);

        $this->batchDownload->expects('markAsFailed');
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveFailsFinishingArchive(): void
    {
        $this->batchDownloadService
            ->expects('getType')
            ->with(Mockery::on(function (BatchDownloadScope $scope): bool {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->batchDownloadType);

        $this->batchDownloadType
            ->expects('getFileBaseName')
            ->with(Mockery::on(function (BatchDownloadScope $scope) {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->fileBaseName);

        $this->archiveNamer
            ->expects('getArchiveName')
            ->with($this->fileBaseName, $this->batchDownload)
            ->andReturn($this->archiveName);

        $this->batchDownload->expects('getDossier')->andReturn($this->wooDecision);
        $this->batchDownload->expects('getInquiry')->andReturn($this->inquiry);

        $this->batchDownloadType->expects('isAvailableForBatchDownload')->andReturnTrue();

        $document = Mockery::mock(Document::class);

        $this->batchDownloadType->expects('getDocumentsQuery->getQuery->getResult')->andReturn([$document]);

        $this->batchArchiver
            ->expects('start')
            ->with($this->batchDownloadType, $this->batchDownload, $this->archiveName);

        $this->batchDownload->expects('setFilename')->with($this->archiveName);
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->batchDownloadService->expects('exists')->with($this->batchDownload)->andReturnTrue();

        $this->batchDownload->expects('getStatus')->andReturn(BatchDownloadStatus::PENDING);
        $this->doctrine->expects('refresh')->with($this->batchDownload);

        $this->batchArchiver->expects('addDocument')->with($document)->andReturnTrue();

        $this->batchArchiver->expects('finish')->andReturnFalse();

        $this->batchDownload->expects('markAsFailed');
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }

    public function testGenerateArchiveCallsCleanupWhenBatchNoLongerPendingWhenFinishing(): void
    {
        $this->batchDownloadService
            ->expects('getType')
            ->with(Mockery::on(function (BatchDownloadScope $scope): bool {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->batchDownloadType);

        $this->batchDownloadType
            ->expects('getFileBaseName')
            ->with(Mockery::on(function (BatchDownloadScope $scope) {
                return $scope->wooDecision === $this->wooDecision && $scope->inquiry === $this->inquiry;
            }))
            ->andReturn($this->fileBaseName);

        $this->archiveNamer
            ->expects('getArchiveName')
            ->with($this->fileBaseName, $this->batchDownload)
            ->andReturn($this->archiveName);

        $this->batchDownload->expects('getDossier')->andReturn($this->wooDecision);
        $this->batchDownload->expects('getInquiry')->andReturn($this->inquiry);

        $this->batchDownloadType->expects('isAvailableForBatchDownload')->andReturnTrue();

        $documentOne = Mockery::mock(Document::class);
        $documentTwo = Mockery::mock(Document::class);

        $this->batchDownloadType
            ->expects('getDocumentsQuery->getQuery->getResult')
            ->andReturn([$documentOne, $documentTwo]);

        $this->batchArchiver
            ->expects('start')
            ->with($this->batchDownloadType, $this->batchDownload, $this->archiveName);

        $this->batchDownload->expects('setFilename')->with($this->archiveName);
        $this->doctrine->expects('persist')->with($this->batchDownload);
        $this->doctrine->expects('flush');

        $this->batchDownloadService->expects('exists')->with($this->batchDownload)->times(2)->andReturnTrue();

        $this->batchDownload->expects('getStatus')->andReturn(BatchDownloadStatus::PENDING);
        $this->batchDownload->expects('getStatus')->andReturn(BatchDownloadStatus::OUTDATED);
        $this->doctrine->expects('refresh')->with($this->batchDownload)->times(2);

        $this->batchArchiver->expects('addDocument')->with($documentOne)->andReturnTrue();
        $this->batchArchiver->expects('addDocument')->with($documentTwo)->andReturnTrue();

        $batchArchiverResult = new BatchArchiverResult(
            filename: 'archive.zip',
            size: 12345,
            fileCount: 2,
        );

        $this->batchArchiver->expects('finish')->andReturn($batchArchiverResult);

        $this->batchArchiver->expects('cleanup');

        $this->batchDownload->shouldNotReceive('complete');

        $this->assertFalse($this->zipGenerator->generateArchive($this->batchDownload));
    }
}
