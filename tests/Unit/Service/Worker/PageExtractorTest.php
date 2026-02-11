<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageException;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Stats\WorkerStatsService;
use Shared\Service\Worker\Pdf\Extractor\PageExtractor;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkPageExtractResult;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class PageExtractorTest extends UnitTestCase
{
    private PdftkService&MockInterface $pdftkService;
    private WorkerStatsService&MockInterface $statsService;
    private PageExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdftkService = Mockery::mock(PdftkService::class);
        $this->statsService = Mockery::mock(WorkerStatsService::class);
        $this->extractor = new PageExtractor(
            $this->pdftkService,
            $this->statsService,
        );
    }

    public function testThrowsExceptionWhenExtractFails(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());

        $context = Mockery::mock(PdfPageProcessingContext::class);
        $context->shouldReceive('getWorkDirPath')->andReturn('/foo/bar');
        $context->shouldReceive('getLocalDocument')->andReturn('/baz/doc.pdf');
        $context->shouldReceive('getPageNumber')->andReturn(2);
        $context->shouldReceive('getEntity')->andReturn($entity);

        $result = new PdftkPageExtractResult(
            exitCode: 1,
            params: [],
            errorMessage: 'some error',
            sourcePdf: '',
            pageNr: 2,
            targetPath: '',
        );

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('pdftk.extractPage', Mockery::on(function () {
                return true;
            }))
            ->andReturn($result);

        $this->expectException(PdfPageException::class);

        $this->extractor->extractSinglePagePdf($context);
    }

    public function testSetLocalPageDocumentOnSuccess(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());

        $context = Mockery::mock(PdfPageProcessingContext::class);
        $context->shouldReceive('getWorkDirPath')->andReturn('/foo/bar');
        $context->shouldReceive('getLocalDocument')->andReturn('/baz/doc.pdf');
        $context->shouldReceive('getPageNumber')->andReturn(2);
        $context->shouldReceive('getEntity')->andReturn($entity);

        $result = new PdftkPageExtractResult(
            exitCode: 0,
            params: [],
            errorMessage: 'some error',
            sourcePdf: '',
            pageNr: 2,
            targetPath: '',
        );

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('pdftk.extractPage', Mockery::on(function () {
                return true;
            }))
            ->andReturn($result);

        $context->expects('setLocalPageDocument')->with('/foo/bar/page.pdf');

        $this->extractor->extractSinglePagePdf($context);
    }
}
