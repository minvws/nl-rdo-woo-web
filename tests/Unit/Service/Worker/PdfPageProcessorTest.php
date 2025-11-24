<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker;

use Mockery\MockInterface;
use Shared\Domain\Ingest\Content\ContentExtractCache;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageProcessingContextFactory;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageProcessor;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Worker\Pdf\Extractor\PageContentExtractor;
use Shared\Service\Worker\Pdf\Extractor\PageExtractor;
use Shared\Service\Worker\Pdf\Extractor\ThumbnailExtractor;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\InvalidArgumentException;

class PdfPageProcessorTest extends UnitTestCase
{
    private ThumbnailExtractor&MockInterface $thumbnailExtractor;
    private PageExtractor&MockInterface $pageExtractor;
    private PageContentExtractor&MockInterface $pageContentExtractor;
    private PdfPageProcessingContextFactory&MockInterface $contextFactory;
    private ContentExtractCache&MockInterface $contentExtractCache;
    private PdfPageProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->thumbnailExtractor = \Mockery::mock(ThumbnailExtractor::class);
        $this->pageContentExtractor = \Mockery::mock(PageContentExtractor::class);
        $this->pageExtractor = \Mockery::mock(PageExtractor::class);
        $this->contextFactory = \Mockery::mock(PdfPageProcessingContextFactory::class);
        $this->contentExtractCache = \Mockery::mock(ContentExtractCache::class);
        $this->processor = new PdfPageProcessor(
            $this->contextFactory,
            $this->thumbnailExtractor,
            $this->pageContentExtractor,
            $this->contentExtractCache,
            $this->pageExtractor,
        );
    }

    public function testProcessPageThrowsExceptionWhenEntityIsNotPaginatable(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo->isPaginatable')->andReturnFalse();

        $this->expectException(InvalidArgumentException::class);
        $this->processor->processPage($entity, 1);
    }

    public function testProcessPageDoesNothingIfContextIsNull(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo->isPaginatable')->andReturnTrue();

        $this->contextFactory->expects('createContext')->with($entity, 1)->andReturnNull();

        $this->processor->processPage($entity, 1);
    }

    public function testProcessPageCallsAllExtractorsSuccessfulAndInitiatesTeardown(): void
    {
        $pageNr = 1;

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo->isPaginatable')->andReturnTrue();

        $context = \Mockery::mock(PdfPageProcessingContext::class);
        $this->contextFactory->expects('createContext')->with($entity, $pageNr)->andReturn($context);

        $this->contentExtractCache->expects('hasCache')->with($entity, $pageNr)->andReturnFalse();
        $this->pageContentExtractor->expects('extract')->with($context);
        $this->pageExtractor->expects('extractSinglePagePdf')->with($context);

        $this->thumbnailExtractor->expects('needsThumbGeneration')->with($context)->andReturnTrue();
        $this->thumbnailExtractor->expects('extractSinglePagePdfThumbnail')->with($context);

        $this->contextFactory->expects('teardown')->with($context);

        $this->processor->processPage($entity, $pageNr);
    }

    public function testProcessPageCallsSkipsExtractorsWhenNotNeeded(): void
    {
        $pageNr = 1;

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo->isPaginatable')->andReturnTrue();

        $context = \Mockery::mock(PdfPageProcessingContext::class);
        $this->contextFactory->expects('createContext')->with($entity, $pageNr)->andReturn($context);

        $this->thumbnailExtractor->expects('needsThumbGeneration')->with($context)->andReturnFalse();

        $this->contentExtractCache->expects('hasCache')->with($entity, $pageNr)->andReturnTrue();
        $this->pageContentExtractor->expects('extract')->with($context);

        $this->contextFactory->expects('teardown')->with($context);

        $this->processor->processPage($entity, $pageNr);
    }

    public function testProcessPageInitiatesTeardownEvenWhenAnExtractorFails(): void
    {
        $pageNr = 1;

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo->isPaginatable')->andReturnTrue();

        $exception = new \RuntimeException();

        $context = \Mockery::mock(PdfPageProcessingContext::class);
        $this->contextFactory->expects('createContext')->with($entity, 1)->andReturn($context);

        $this->pageExtractor->expects('extractSinglePagePdf')->with($context);
        $this->thumbnailExtractor->expects('extractSinglePagePdfThumbnail')->andThrows($exception);
        $this->thumbnailExtractor->expects('needsThumbGeneration')->with($context)->andReturnTrue();

        $this->contentExtractCache->expects('hasCache')->with($entity, $pageNr);

        $this->contextFactory->expects('teardown')->with($context);

        $this->expectExceptionObject($exception);

        $this->processor->processPage($entity, $pageNr);
    }
}
