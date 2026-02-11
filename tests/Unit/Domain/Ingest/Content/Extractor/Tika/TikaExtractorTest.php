<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content\Extractor\Tika;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Ingest\Content\ContentExtractLogContext;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use Shared\Domain\Ingest\Content\Extractor\Tika\TikaExtractor;
use Shared\Domain\Ingest\Content\Extractor\Tika\TikaService;
use Shared\Domain\Ingest\Content\LazyFileReference;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class TikaExtractorTest extends UnitTestCase
{
    private TikaService&MockInterface $tikaService;
    private TikaExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tikaService = Mockery::mock(TikaService::class);
        $this->extractor = new TikaExtractor($this->tikaService);
    }

    public function testGetContent(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getNormalizedMimeType')->andReturn($mimeType = 'text/plain');

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $entity->shouldReceive('getId')->andReturn($id = Uuid::v6());

        $fileReference = Mockery::mock(LazyFileReference::class);
        $fileReference->shouldReceive('getPath')->andReturn($file = '/foo/bar.txt');

        $this->tikaService
            ->expects('extract')
            ->with(
                $file,
                $mimeType,
                Mockery::on(
                    static function (ContentExtractLogContext $context) use ($id): bool {
                        self::assertEquals($id->toRfc4122(), $context->id);

                        return true;
                    }
                ),
            )
            ->andReturn(['X-TIKA:content' => '   foo bar  ']);

        self::assertEquals(
            'foo bar',
            $this->extractor->getContent($entity, $fileReference),
        );
    }

    public function testSupports(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $fileInfo->shouldReceive('getNormalizedMimeType')->once()->andReturn('text/plain');
        self::assertTrue($this->extractor->supports($entity));

        $fileInfo->shouldReceive('getNormalizedMimeType')->once()->andReturn('application/pdf');
        self::assertTrue($this->extractor->supports($entity));

        $fileInfo->shouldReceive('getNormalizedMimeType')->once()->andReturn('');
        self::assertFalse($this->extractor->supports($entity));
    }

    public function testGetKey(): void
    {
        self::assertEquals(
            ContentExtractorKey::TIKA,
            $this->extractor->getKey(),
        );
    }
}
