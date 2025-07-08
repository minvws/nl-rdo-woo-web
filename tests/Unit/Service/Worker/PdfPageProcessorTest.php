<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker;

use App\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use App\Domain\Ingest\Process\PdfPage\PdfPageProcessingContextFactory;
use App\Domain\Ingest\Process\PdfPage\PdfPageProcessor;
use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Worker\Pdf\Extractor\PageContentExtractor;
use App\Service\Worker\Pdf\Extractor\PageExtractor;
use App\Service\Worker\Pdf\Extractor\ThumbnailExtractor;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Webmozart\Assert\InvalidArgumentException;

class PdfPageProcessorTest extends UnitTestCase
{
    private ThumbnailExtractor&MockInterface $thumbnailExtractor;
    private PageExtractor&MockInterface $pageExtractor;
    private PageContentExtractor&MockInterface $pageContentExtractor;
    private PdfPageProcessingContextFactory&MockInterface $contextFactory;
    private PdfPageProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->thumbnailExtractor = \Mockery::mock(ThumbnailExtractor::class);
        $this->pageContentExtractor = \Mockery::mock(PageContentExtractor::class);
        $this->pageExtractor = \Mockery::mock(PageExtractor::class);
        $this->contextFactory = \Mockery::mock(PdfPageProcessingContextFactory::class);
        $this->processor = new PdfPageProcessor(
            $this->contextFactory,
            $this->thumbnailExtractor,
            $this->pageContentExtractor,
            $this->pageExtractor,
        );
    }

    public function testProcessPageThrowsExceptionWhenEntityIsNotPaginatable(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo->isPaginatable')->andReturnFalse();

        $this->expectException(InvalidArgumentException::class);
        $this->processor->processPage($entity, 1, true);
    }

    public function testProcessPageCallsAllExtractorsSuccessfulAndInitiatesTeardown(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo->isPaginatable')->andReturnTrue();

        $context = \Mockery::mock(PdfPageProcessingContext::class);
        $this->contextFactory->expects('createContext')->with($entity, 1)->andReturn($context);

        $this->pageContentExtractor->expects('extract')->with($context, true);
        $this->pageExtractor->expects('extractSinglePagePdf')->with($context);
        $this->thumbnailExtractor->expects('extractSinglePagePdfThumbnail')->with($context);

        $this->contextFactory->expects('teardown')->with($context);

        $this->processor->processPage($entity, 1, true);
    }

    public function testProcessPageInitiatesTeardownEvenWhenAnExtractorFails(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo->isPaginatable')->andReturnTrue();

        $exception = new \RuntimeException();

        $context = \Mockery::mock(PdfPageProcessingContext::class);
        $this->contextFactory->expects('createContext')->with($entity, 1)->andReturn($context);

        $this->pageExtractor->expects('extractSinglePagePdf')->with($context);
        $this->thumbnailExtractor->expects('extractSinglePagePdfThumbnail')->andThrows($exception);

        $this->contextFactory->expects('teardown')->with($context);

        $this->expectExceptionObject($exception);

        $this->processor->processPage($entity, 1, true);
    }
}
