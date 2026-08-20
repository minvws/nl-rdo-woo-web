<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageException;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Worker\Pdf\Extractor\PageExtractor;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkPageExtractResult;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class PageExtractorTest extends UnitTestCase
{
    private PdftkService&MockInterface $pdftkService;
    private PageExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdftkService = Mockery::mock(PdftkService::class);
        $this->extractor = new PageExtractor(
            $this->pdftkService,
        );
    }

    public function testThrowsExceptionWhenExtractFails(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getId')->andReturn(Uuid::v6());

        $context = Mockery::mock(PdfPageProcessingContext::class);
        $context->expects('getWorkDirPath')->andReturn('/foo/bar');
        $context->expects('getLocalDocument')->andReturn($this->getFaker()->word());
        $context->expects('getPageNumber')->twice()->andReturn(2);
        $context->expects('getEntity')->twice()->andReturn($entity);

        $result = new PdftkPageExtractResult(
            exitCode: 1,
            params: [],
            errorMessage: 'some error',
            sourcePdf: '',
            pageNr: 2,
            targetPath: '',
        );

        $this->pdftkService->expects('extractPage')->andReturn($result);

        $this->expectException(PdfPageException::class);

        $this->extractor->extractSinglePagePdf($context);
    }

    public function testSetLocalPageDocumentOnSuccess(): void
    {
        $page = $this->getFaker()->numberBetween(1, 10);
        $context = Mockery::mock(PdfPageProcessingContext::class);
        $context->expects('getWorkDirPath')->andReturn('/foo/bar');
        $context->expects('getLocalDocument')->andReturn($this->getFaker()->word());
        $context->expects('getPageNumber')->andReturn($page);

        $result = new PdftkPageExtractResult(
            exitCode: 0,
            params: [],
            errorMessage: 'some error',
            sourcePdf: '',
            pageNr: $page,
            targetPath: '',
        );

        $this->pdftkService->expects('extractPage')->andReturn($result);

        $context->expects('setLocalPageDocument')->with('/foo/bar/page.pdf');

        $this->extractor->extractSinglePagePdf($context);
    }
}
