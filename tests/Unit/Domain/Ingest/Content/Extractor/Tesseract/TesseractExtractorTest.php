<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content\Extractor\Tesseract;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use Shared\Domain\Ingest\Content\Extractor\Tesseract\TesseractExtractor;
use Shared\Domain\Ingest\Content\Extractor\Tesseract\TesseractService;
use Shared\Domain\Ingest\Content\LazyFileReference;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Unit\UnitTestCase;

final class TesseractExtractorTest extends UnitTestCase
{
    private TesseractService&MockInterface $tesseractService;
    private TesseractExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tesseractService = Mockery::mock(TesseractService::class);
        $this->extractor = new TesseractExtractor($this->tesseractService);
    }

    public function testGetContent(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);

        $fileReference = Mockery::mock(LazyFileReference::class);
        $fileReference->expects('getPath')->andReturn($file = '/foo/bar.txt');

        $this->tesseractService->expects('extract')->with($file)->andReturn('   foo bar  ');

        self::assertEquals(
            'foo bar',
            $this->extractor->getContent($entity, $fileReference),
        );
    }

    public function testSupports(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->times(2)->andReturn($fileInfo);

        $fileInfo->expects('getNormalizedMimeType')->andReturn('text/plain');
        self::assertFalse($this->extractor->supports($entity));

        $fileInfo->expects('getNormalizedMimeType')->andReturn('application/pdf');
        self::assertTrue($this->extractor->supports($entity));
    }

    public function testGetKey(): void
    {
        self::assertEquals(
            ContentExtractorKey::TESSERACT,
            $this->extractor->getKey(),
        );
    }
}
