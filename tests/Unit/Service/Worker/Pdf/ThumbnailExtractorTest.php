<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageException;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\Service\Worker\Pdf\Extractor\ThumbnailExtractor;
use Shared\Service\Worker\Pdf\Tools\Pdftoppm\PdftoppmService;
use Shared\Service\Worker\Pdf\Tools\Pdftoppm\PdftoppmThumbnailResult;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class ThumbnailExtractorTest extends UnitTestCase
{
    private PdftoppmService&MockInterface $pdfToPpmService;
    private ThumbnailStorageService&MockInterface $thumbnailStorageService;
    private EntityWithFileInfo&MockInterface $entity;
    private int $thumbnailLimit = 50;
    private ThumbnailExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->thumbnailStorageService = Mockery::mock(ThumbnailStorageService::class);
        $this->pdfToPpmService = Mockery::mock(PdftoppmService::class);
        $this->entity = Mockery::mock(EntityWithFileInfo::class);

        $this->extractor = new ThumbnailExtractor(
            $this->thumbnailStorageService,
            $this->pdfToPpmService,
            $this->thumbnailLimit,
        );
    }

    public function testExtractSuccessful(): void
    {
        $pageNr = 23;
        $pageContents = $this->getFaker()->sha256();
        $thumbnail = $this->getFaker()->sha256();

        $context = new PdfPageProcessingContext($this->entity, $pageNr);
        $context->setPageContents($pageContents);

        $pdfToPpmResult = new PdftoppmThumbnailResult(
            exitCode: 0,
            params: [],
            errorMessage: null,
            thumbnail: $thumbnail,
        );

        $this->pdfToPpmService
            ->expects('createThumbnail')
            ->with($pageContents)
            ->andReturn($pdfToPpmResult);

        $this->thumbnailStorageService
            ->expects('store')
            ->with($this->entity, $thumbnail, $pageNr);

        $this->extractor->extractSinglePagePdfThumbnail($context);
    }

    public function testExtractSkipsWhenPageNrAboveLimit(): void
    {
        $pageNr = 51;
        $context = new PdfPageProcessingContext($this->entity, $pageNr);
        $context->setPageContents($this->getFaker()->sha256());

        $this->pdfToPpmService->expects('createThumbnail')->never();

        $this->extractor->extractSinglePagePdfThumbnail($context);
    }

    public function testExtractThrowsExceptionOnFailure(): void
    {
        $this->entity->expects('getId')->andReturn(Uuid::v6());

        $pageNr = 23;
        $pageContents = $this->getFaker()->sha256();

        $context = new PdfPageProcessingContext($this->entity, $pageNr);
        $context->setPageContents($pageContents);

        $pdfToPpmResult = new PdftoppmThumbnailResult(
            exitCode: 1,
            params: [],
            errorMessage: $this->getFaker()->sentence(),
            thumbnail: null,
        );

        $this->pdfToPpmService
            ->expects('createThumbnail')
            ->with($pageContents)
            ->andReturn($pdfToPpmResult);

        $this->thumbnailStorageService->expects('store')->never();

        $this->expectException(PdfPageException::class);

        $this->extractor->extractSinglePagePdfThumbnail($context);
    }

    public function testNeedsThumbGenerationReturnsFalseForPageNumberExceedingLimit(): void
    {
        $pageNr = 345;
        $context = Mockery::mock(PdfPageProcessingContext::class);
        $context->expects('getPageNumber')->andReturn($pageNr);

        self::assertFalse($this->extractor->needsThumbGeneration($context));
    }

    public function testNeedsThumbGenerationReturnsTrueWhenThumbIsMissing(): void
    {
        $pageNr = 1;
        $entity = Mockery::mock(EntityWithFileInfo::class);

        $context = Mockery::mock(PdfPageProcessingContext::class);
        $context->expects('getPageNumber')->times(2)->andReturn($pageNr);
        $context->expects('getEntity')->andReturn($entity);

        $this->thumbnailStorageService->expects('exists')->with($entity, $pageNr)->andReturnFalse();

        self::assertTrue($this->extractor->needsThumbGeneration($context));
    }

    public function testNeedsThumbGenerationReturnsFalseWhenThumbAlreadyExists(): void
    {
        $pageNr = 1;
        $entity = Mockery::mock(EntityWithFileInfo::class);

        $context = Mockery::mock(PdfPageProcessingContext::class);
        $context->expects('getPageNumber')->times(2)->andReturn($pageNr);
        $context->expects('getEntity')->andReturn($entity);

        $this->thumbnailStorageService->expects('exists')->with($entity, $pageNr)->andReturnTrue();

        self::assertFalse($this->extractor->needsThumbGeneration($context));
    }
}
