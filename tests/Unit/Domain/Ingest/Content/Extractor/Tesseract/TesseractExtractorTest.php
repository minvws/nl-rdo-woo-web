<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Content\Extractor\Tesseract;

use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Ingest\Content\Extractor\Tesseract\TesseractExtractor;
use App\Domain\Ingest\Content\Extractor\Tesseract\TesseractService;
use App\Domain\Ingest\Content\LazyFileReference;
use App\Entity\FileInfo;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class TesseractExtractorTest extends MockeryTestCase
{
    private TesseractService&MockInterface $tesseractService;
    private TesseractExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tesseractService = \Mockery::mock(TesseractService::class);
        $this->extractor = new TesseractExtractor($this->tesseractService);
    }

    public function testGetContent(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getNormalizedMimeType')->andReturn($mimeType = 'text/plain');

        $fileReference = \Mockery::mock(LazyFileReference::class);
        $fileReference->shouldReceive('getPath')->andReturn($file = '/foo/bar.txt');

        $this->tesseractService->expects('extract')->with($file)->andReturn('   foo bar  ');

        self::assertEquals(
            'foo bar',
            $this->extractor->getContent($fileInfo, $fileReference),
        );
    }

    public function testSupports(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);

        $fileInfo->shouldReceive('getNormalizedMimeType')->once()->andReturn('text/plain');
        self::assertFalse($this->extractor->supports($fileInfo));

        $fileInfo->shouldReceive('getNormalizedMimeType')->once()->andReturn('application/pdf');
        self::assertTrue($this->extractor->supports($fileInfo));
    }

    public function testGetKey(): void
    {
        self::assertEquals(
            ContentExtractorKey::TESSERACT,
            $this->extractor->getKey(),
        );
    }
}
