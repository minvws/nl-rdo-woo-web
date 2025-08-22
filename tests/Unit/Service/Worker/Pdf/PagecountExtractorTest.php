<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker\Pdf;

use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Stats\WorkerStatsService;
use App\Service\Storage\EntityStorageService;
use App\Service\Worker\Pdf\Extractor\PagecountExtractor;
use App\Service\Worker\Pdf\Tools\Pdftk\PdftkPageCountResult;
use App\Service\Worker\Pdf\Tools\Pdftk\PdftkService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class PagecountExtractorTest extends UnitTestCase
{
    protected LoggerInterface&MockInterface $logger;
    protected PdftkService&MockInterface $pdftkService;
    protected EntityStorageService&MockInterface $entityStorageService;
    protected WorkerStatsService&MockInterface $statsService;
    protected EntityWithFileInfo&MockInterface $entity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->pdftkService = \Mockery::mock(PdftkService::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->statsService = \Mockery::mock(WorkerStatsService::class);
        $this->entity = \Mockery::mock(EntityWithFileInfo::class);
    }

    public function testExtract(): void
    {
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

        $pdftkPageCountResult = new PdftkPageCountResult(
            exitCode: 0,
            params: ['cmd', 'param1', 'param2'],
            errorMessage: null,
            sourcePdf: 'foobar.pdf',
            numberOfPages: 42,
        );

        $this->pdftkService
            ->shouldReceive('extractNumberOfPages')
            ->once()
            ->with($localPdfPath)
            ->andReturn($pdftkPageCountResult);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('pdftk.extractNumberOfPages', \Mockery::on(function (\Closure $closure) use ($pdftkPageCountResult) {
                $result = $closure();

                $this->assertSame($pdftkPageCountResult, $result, 'The pdftkPageCountResult does not match expected value');

                return true;
            }))
            ->andReturn($pdftkPageCountResult);

        $this->entityStorageService
            ->shouldReceive('removeDownload')
            ->once()
            ->with($localPdfPath);

        $extractor = new PagecountExtractor(
            $this->logger,
            $this->pdftkService,
            $this->entityStorageService,
            $this->statsService,
        );

        $extractor->extract($this->entity, forceRefresh: false);

        $this->assertSame($pdftkPageCountResult, $extractor->getOutput());
    }

    public function testExtractWithFailedDownloadingOfEntity(): void
    {
        $this->entityStorageService
            ->shouldReceive('downloadEntity')
            ->once()
            ->with($this->entity)
            ->andReturnFalse();

        $this->entity
            ->shouldReceive('getId')
            ->once()
            ->andReturn($entityUuid = \Mockery::mock(Uuid::class));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to download entity for page count extraction', [
                'id' => $entityUuid,
                'class' => $this->entity::class,
            ]);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('download.entity', \Mockery::on(function (\Closure $closure) {
                $result = $closure();

                $this->assertFalse($result);

                return true;
            }))
            ->andReturnFalse();

        $extractor = new PagecountExtractor(
            $this->logger,
            $this->pdftkService,
            $this->entityStorageService,
            $this->statsService,
        );

        $extractor->extract($this->entity, forceRefresh: false);

        $this->assertNull($extractor->getOutput());
    }

    public function testExtractWithFailedNumberOfPagesExtraction(): void
    {
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

        $pdftkPageCountResult = new PdftkPageCountResult(
            exitCode: 1,
            params: [],
            errorMessage: 'errorMessage',
            sourcePdf: 'sourcePdf',
            numberOfPages: null,
        );

        $this->pdftkService
            ->shouldReceive('extractNumberOfPages')
            ->once()
            ->with($localPdfPath)
            ->andReturn($pdftkPageCountResult);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('pdftk.extractNumberOfPages', \Mockery::on(function (\Closure $closure) use ($pdftkPageCountResult) {
                $result = $closure();

                $this->assertSame($pdftkPageCountResult, $result, 'The pdftkPageCountResult does not match expected value');

                return true;
            }))
            ->andReturn($pdftkPageCountResult);

        $this->entityStorageService
            ->shouldReceive('removeDownload')
            ->once()
            ->with($localPdfPath);

        $this->entity
            ->shouldReceive('getId')
            ->once()
            ->andReturn($entityUuid = \Mockery::mock(Uuid::class));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to get number of pages', [
                'id' => $entityUuid,
                'class' => $this->entity::class,
                'sourcePdf' => $pdftkPageCountResult->sourcePdf,
                'errorOutput' => $pdftkPageCountResult->errorMessage,
            ]);

        $extractor = new PagecountExtractor(
            $this->logger,
            $this->pdftkService,
            $this->entityStorageService,
            $this->statsService,
        );

        $extractor->extract($this->entity, forceRefresh: false);

        $this->assertSame($pdftkPageCountResult, $extractor->getOutput());
    }
}
