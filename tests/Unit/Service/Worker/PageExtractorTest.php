<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker;

use GuzzleHttp\Psr7\Utils;
use Mockery;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageException;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Worker\Pdf\Extractor\PageExtractor;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkPageExtractResult;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class PageExtractorTest extends UnitTestCase
{
    public function testThrowsExceptionWhenReadingFromStorageFails(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getId')->andReturn(Uuid::v6());

        $context = new PdfPageProcessingContext($entity, $this->getFaker()->numberBetween(1, 10));

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService->expects('retrieveResourceEntity')->with($entity)->andReturnNull();

        $extractor = new PageExtractor(Mockery::mock(PdftkService::class), $entityStorageService);

        $this->expectException(PdfPageException::class);

        $extractor->extractSinglePagePdf($context);
    }

    public function testThrowsExceptionWhenExtractFails(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getId')->andReturn(Uuid::v6());

        $pageNr = $this->getFaker()->numberBetween(1, 10);
        $context = new PdfPageProcessingContext($entity, $pageNr);
        $source = Utils::tryFopen('php://memory', 'r');

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService->expects('retrieveResourceEntity')->with($entity)->andReturn($source);

        $result = new PdftkPageExtractResult(
            exitCode: 1,
            params: [],
            errorMessage: $this->getFaker()->sentence(),
            pageNr: $pageNr,
            pageContents: null,
        );

        $pdftkService = Mockery::mock(PdftkService::class);
        $pdftkService->expects('extractPage')->with($source, $pageNr)->andReturn($result);

        $extractor = new PageExtractor($pdftkService, $entityStorageService);

        $this->expectException(PdfPageException::class);

        $extractor->extractSinglePagePdf($context);
    }

    public function testSetsPageContentsOnSuccess(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);

        $pageNr = $this->getFaker()->numberBetween(1, 10);
        $pageContents = $this->getFaker()->sha256();
        $context = new PdfPageProcessingContext($entity, $pageNr);
        $source = Utils::tryFopen('php://memory', 'r');

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService->expects('retrieveResourceEntity')->with($entity)->andReturn($source);

        $result = new PdftkPageExtractResult(
            exitCode: 0,
            params: [],
            errorMessage: null,
            pageNr: $pageNr,
            pageContents: $pageContents,
        );

        $pdftkService = Mockery::mock(PdftkService::class);
        $pdftkService->expects('extractPage')->with($source, $pageNr)->andReturn($result);

        $extractor = new PageExtractor($pdftkService, $entityStorageService);
        $extractor->extractSinglePagePdf($context);

        self::assertSame($pageContents, $context->getPageContents());
    }
}
