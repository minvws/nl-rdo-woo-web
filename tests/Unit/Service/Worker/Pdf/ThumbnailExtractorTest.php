<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker\Pdf;

use App\Domain\Ingest\Process\PdfPage\PdfPageException;
use App\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Storage\ThumbnailStorageService;
use App\Service\Worker\Pdf\Extractor\ThumbnailExtractor;
use App\Service\Worker\Pdf\Tools\Pdftoppm\PdftoppmService;
use App\Service\Worker\Pdf\Tools\Pdftoppm\PdftoppmThumbnailResult;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\HttpFoundation\File\File;
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

        $this->thumbnailStorageService = \Mockery::mock(ThumbnailStorageService::class);
        $this->pdfToPpmService = \Mockery::mock(PdftoppmService::class);
        $this->entity = \Mockery::mock(EntityWithFileInfo::class);

        $this->extractor = new ThumbnailExtractor(
            $this->thumbnailStorageService,
            $this->pdfToPpmService,
            $this->thumbnailLimit,
        );

        vfsStream::setup();
    }

    public function testExtractSuccessful(): void
    {
        $pageNr = 23;
        $workDir = 'vfs://root/temp';
        $localDocument = '/baz.pdf';
        $context = new PdfPageProcessingContext($this->entity, $pageNr, $workDir, $localDocument);
        $context->setLocalPageDocument($localPageDocument = '/baz_123.pdf');

        $targetPath = $workDir . '/thumb';

        $pdfToPpmResult = new PdftoppmThumbnailResult(
            exitCode: 0,
            params: [],
            errorMessage: null,
            sourcePdf: $localDocument,
            targetPath: $targetPath,
        );

        $this->pdfToPpmService
            ->expects('createThumbnail')
            ->with($localPageDocument, $targetPath)
            ->andReturn($pdfToPpmResult);

        $this->thumbnailStorageService
            ->expects('store')
            ->with(
                $this->entity,
                \Mockery::on(function (File $file) use ($targetPath) {
                    $this->assertSame($targetPath . '.png', $file->getPathname());

                    return true;
                }),
                $pageNr,
            );

        vfsStream::create(['temp' => ['thumb.png' => '']]);

        $this->extractor->extractSinglePagePdfThumbnail($context);
    }

    public function testExtractSkipsWhenPageNrAboveLimit(): void
    {
        $pageNr = 51;
        $workDir = 'vfs://root/temp';
        $localDocument = '/baz.pdf';
        $context = new PdfPageProcessingContext($this->entity, $pageNr, $workDir, $localDocument);
        $context->setLocalPageDocument('/baz_123.pdf');

        $this->pdfToPpmService->shouldNotReceive('createThumbnail');

        $this->extractor->extractSinglePagePdfThumbnail($context);
    }

    public function testExtractThrowsExceptionOnFailure(): void
    {
        $this->entity->expects('getId')->andReturn(Uuid::v6());

        $pageNr = 23;
        $workDir = 'vfs://root/temp';
        $localDocument = '/baz.pdf';
        $context = new PdfPageProcessingContext($this->entity, $pageNr, $workDir, $localDocument);
        $context->setLocalPageDocument($localPageDocument = '/baz_123.pdf');

        $targetPath = $workDir . '/thumb';

        $pdfToPpmResult = new PdftoppmThumbnailResult(
            exitCode: 1,
            params: [],
            errorMessage: 'oops',
            sourcePdf: $localDocument,
            targetPath: $targetPath,
        );

        $this->pdfToPpmService
            ->expects('createThumbnail')
            ->with($localPageDocument, $targetPath)
            ->andReturn($pdfToPpmResult);

        $this->thumbnailStorageService->shouldNotReceive('store');

        $this->expectException(PdfPageException::class);

        $this->extractor->extractSinglePagePdfThumbnail($context);
    }
}
