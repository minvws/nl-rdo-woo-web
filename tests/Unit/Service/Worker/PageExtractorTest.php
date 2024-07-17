<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker;

use App\Entity\EntityWithFileInfo;
use App\Entity\FileInfo;
use App\Service\Stats\WorkerStatsService;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\LocalFilesystem;
use App\Service\Storage\ThumbnailStorageService;
use App\Service\Worker\Pdf\Extractor\PageExtractor;
use App\Service\Worker\Pdf\Tools\Pdftk\PdftkPageExtractResult;
use App\Service\Worker\Pdf\Tools\Pdftk\PdftkService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class PageExtractorTest extends UnitTestCase
{
    protected LoggerInterface&MockInterface $logger;
    protected PdftkService&MockInterface $pdftkService;
    protected ThumbnailStorageService&MockInterface $thumbnailStorageService;
    protected EntityStorageService&MockInterface $entityStorageService;
    protected WorkerStatsService&MockInterface $statsService;
    protected LocalFilesystem&MockInterface $localFilesystem;
    protected EntityWithFileInfo&MockInterface $entity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->pdftkService = \Mockery::mock(PdftkService::class);
        $this->thumbnailStorageService = \Mockery::mock(ThumbnailStorageService::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->statsService = \Mockery::mock(WorkerStatsService::class);
        $this->localFilesystem = \Mockery::mock(LocalFilesystem::class);
        $this->entity = \Mockery::mock(EntityWithFileInfo::class);
    }

    public function testExtract(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isPaginatable')->once()->andReturnTrue();

        $this->entity
            ->shouldReceive('getFileInfo')
            ->once()
            ->andReturn($fileInfo);

        $this->entityStorageService
            ->shouldReceive('downloadEntity')
            ->once()
            ->with($this->entity)
            ->andReturn($localPdfPath = 'localPdfPath');

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) use ($localPdfPath) {
                $result = $closure();

                $this->assertSame($localPdfPath, $result, 'The download path does not match expected value');

                return true;
            }))
            ->andReturn($localPdfPath);

        $this->localFilesystem
            ->shouldReceive('createTempDir')
            ->once()
            ->andReturn($tempDir = 'tempDir');

        $pdftkPageExtractResult = new PdftkPageExtractResult(
            exitCode: 0,
            params: [],
            errorMessage: null,
            sourcePdf: $localPdfPath,
            pageNr: $pageNr = 42,
            targetPath: $targetPath = $tempDir . '/page.pdf',
        );

        $this->pdftkService
            ->shouldReceive('extractPage')
            ->once()
            ->with($localPdfPath, $pageNr, $targetPath)
            ->andReturn($pdftkPageExtractResult);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('pdftk.extractPage', \Mockery::on(function (\Closure $closure) use ($pdftkPageExtractResult) {
                $result = $closure();

                $this->assertSame($pdftkPageExtractResult, $result, 'The pdftkPageExtractResult does not match expected value');

                return true;
            }))
            ->andReturn($pdftkPageExtractResult);

        $this->entityStorageService
            ->shouldReceive('removeDownload')
            ->once()
            ->with($localPdfPath);

        $this->entityStorageService
            ->shouldReceive('storePage')
            ->once()
            ->with(\Mockery::on(function (\SplFileInfo $file) use ($targetPath) {
                $this->assertSame($file->getPathname(), $targetPath);

                return true;
            }), $this->entity, $pageNr);

        $this->localFilesystem
            ->shouldReceive('deleteDirectory')
            ->once();

        $this->getExtractor()->extract($this->entity, $pageNr, false);
    }

    public function testExtractWithFailedDownloadingEntity(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isPaginatable')->once()->andReturnTrue();

        $this->entity
            ->shouldReceive('getFileInfo')
            ->once()
            ->andReturn($fileInfo);
        $this->entity
            ->shouldReceive('getId')
            ->once()
            ->andReturn($entityUuid = \Mockery::mock(Uuid::class));

        $this->entityStorageService
            ->shouldReceive('downloadEntity')
            ->once()
            ->with($this->entity)
            ->andReturnFalse();

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) {
                $result = $closure();

                $this->assertFalse($result);

                return true;
            }))
            ->andReturnFalse();

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('cannot download entity from storage', [
                'id' => $entityUuid,
                'class' => $this->entity::class,
            ]);

        $this->getExtractor()->extract($this->entity, 42, false);
    }

    public function testExtractWithFailedCreatingTempDir(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isPaginatable')->once()->andReturnTrue();

        $this->entity
            ->shouldReceive('getFileInfo')
            ->once()
            ->andReturn($fileInfo);
        $this->entity
            ->shouldReceive('getId')
            ->once()
            ->andReturn($entityUuid = \Mockery::mock(Uuid::class));

        $this->entityStorageService
            ->shouldReceive('downloadEntity')
            ->once()
            ->with($this->entity)
            ->andReturn($localPdfPath = 'localPdfPath');

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) use ($localPdfPath) {
                $result = $closure();

                $this->assertSame($localPdfPath, $result, 'The download path does not match expected value');

                return true;
            }))
            ->andReturn($localPdfPath);

        $this->localFilesystem
            ->shouldReceive('createTempDir')
            ->once()
            ->andReturnFalse();

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed creating temp dir', [
                'id' => $entityUuid,
                'class' => $this->entity::class,
            ]);

        $this->getExtractor()->extract($this->entity, 42, false);
    }

    public function testExtractWithFailPage(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isPaginatable')->once()->andReturnTrue();

        $this->entity
            ->shouldReceive('getFileInfo')
            ->once()
            ->andReturn($fileInfo);
        $this->entity
            ->shouldReceive('getId')
            ->once()
            ->andReturn($entityUuid = \Mockery::mock(Uuid::class));

        $this->entityStorageService
            ->shouldReceive('downloadEntity')
            ->once()
            ->with($this->entity)
            ->andReturn($localPdfPath = 'localPdfPath');

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) use ($localPdfPath) {
                $result = $closure();

                $this->assertSame($localPdfPath, $result, 'The download path does not match expected value');

                return true;
            }))
            ->andReturn($localPdfPath);

        $this->localFilesystem
            ->shouldReceive('createTempDir')
            ->once()
            ->andReturn($tempDir = 'tempDir');

        $pdftkPageExtractResult = new PdftkPageExtractResult(
            exitCode: 1,
            params: [],
            errorMessage: 'An error message',
            sourcePdf: $localPdfPath,
            pageNr: $pageNr = 42,
            targetPath: $targetPath = $tempDir . '/page.pdf',
        );

        $this->pdftkService
            ->shouldReceive('extractPage')
            ->once()
            ->with($localPdfPath, $pageNr, $targetPath)
            ->andReturn($pdftkPageExtractResult);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('pdftk.extractPage', \Mockery::on(function (\Closure $closure) use ($pdftkPageExtractResult) {
                $result = $closure();

                $this->assertSame($pdftkPageExtractResult, $result, 'The pdftkPageExtractResult does not match expected value');

                return true;
            }))
            ->andReturn($pdftkPageExtractResult);

        $this->entityStorageService
            ->shouldReceive('removeDownload')
            ->once()
            ->with($localPdfPath);

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to fetch PDF page', [
                'id' => $entityUuid,
                'class' => $this->entity::class,
                'pageNr' => $pdftkPageExtractResult->pageNr,
                'sourcePdf' => $pdftkPageExtractResult->sourcePdf,
                'targetPath' => $pdftkPageExtractResult->targetPath,
                'errorOutput' => $pdftkPageExtractResult->errorMessage,
            ]);

        $this->localFilesystem
            ->shouldReceive('deleteDirectory')
            ->once()
            ->with($tempDir);

        $this->getExtractor()->extract($this->entity, $pageNr, false);
    }

    private function getExtractor(): PageExtractor
    {
        return new PageExtractor(
            $this->logger,
            $this->pdftkService,
            $this->thumbnailStorageService,
            $this->entityStorageService,
            $this->statsService,
            $this->localFilesystem,
        );
    }
}
