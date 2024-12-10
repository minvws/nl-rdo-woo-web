<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Content\Extractor\Tika;

use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Ingest\Content\Extractor\Tika\TikaExtractor;
use App\Domain\Ingest\Content\Extractor\Tika\TikaService;
use App\Domain\Ingest\Content\LazyFileReference;
use App\Domain\Publication\FileInfo;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class TikaExtractorTest extends MockeryTestCase
{
    private TikaService&MockInterface $tikaService;
    private TikaExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tikaService = \Mockery::mock(TikaService::class);
        $this->extractor = new TikaExtractor($this->tikaService);
    }

    public function testGetContent(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getNormalizedMimeType')->andReturn($mimeType = 'text/plain');

        $fileReference = \Mockery::mock(LazyFileReference::class);
        $fileReference->shouldReceive('getPath')->andReturn($file = '/foo/bar.txt');

        $this->tikaService
            ->expects('extract')
            ->with($file, $mimeType)
            ->andReturn(['X-TIKA:content' => '   foo bar  ']);

        self::assertEquals(
            'foo bar',
            $this->extractor->getContent($fileInfo, $fileReference),
        );
    }

    public function testSupports(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);

        $fileInfo->shouldReceive('getNormalizedMimeType')->once()->andReturn('text/plain');
        self::assertTrue($this->extractor->supports($fileInfo));

        $fileInfo->shouldReceive('getNormalizedMimeType')->once()->andReturn('application/pdf');
        self::assertTrue($this->extractor->supports($fileInfo));

        $fileInfo->shouldReceive('getNormalizedMimeType')->once()->andReturn('');
        self::assertFalse($this->extractor->supports($fileInfo));
    }

    public function testGetKey(): void
    {
        self::assertEquals(
            ContentExtractorKey::TIKA,
            $this->extractor->getKey(),
        );
    }
}
