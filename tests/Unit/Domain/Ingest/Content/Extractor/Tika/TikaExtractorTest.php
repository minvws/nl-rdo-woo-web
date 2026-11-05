<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content\Extractor\Tika;

use GuzzleHttp\Psr7\Utils;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\StreamInterface;
use Shared\Domain\Ingest\Content\ContentExtractLogContext;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use Shared\Domain\Ingest\Content\Extractor\Tika\TikaExtractor;
use Shared\Domain\Ingest\Content\Extractor\Tika\TikaService;
use Shared\Domain\Ingest\Content\FileReferenceInterface;
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
        $fileInfo->expects('getNormalizedMimeType')->andReturn($mimeType = 'text/plain');

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);
        $entity->expects('getId')->andReturn($id = Uuid::v6());

        $source = Utils::tryFopen('php://memory', 'r');

        $fileReference = Mockery::mock(FileReferenceInterface::class);
        $fileReference->expects('getStream')->andReturn($source);

        $this->tikaService
            ->expects('extract')
            ->with(
                Mockery::on(static function (StreamInterface $stream) use ($source): bool {
                    return $stream->detach() === $source;
                }),
                $mimeType,
                Mockery::on(
                    static function (ContentExtractLogContext $context) use ($id): bool {
                        self::assertEquals($id->toRfc4122(), $context->id);

                        return true;
                    },
                ),
            )
            ->andReturn(['X-TIKA:content' => '   foo bar  ']);

        self::assertEquals(
            'foo bar',
            $this->extractor->getContent($entity, $fileReference),
        );
    }

    public function testGetContentReturnsEmptyStringWhenTikaReturnsNoContent(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getNormalizedMimeType')->andReturn($this->getFaker()->mimeType());

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);
        $entity->expects('getId')->andReturn(Uuid::v6());

        $fileReference = Mockery::mock(FileReferenceInterface::class);
        $fileReference->expects('getStream')->andReturn(Utils::tryFopen('php://memory', 'r'));

        $tikaService = Mockery::mock(TikaService::class);
        $tikaService->expects('extract')->andReturn([]);

        $extractor = new TikaExtractor($tikaService);

        self::assertSame('', $extractor->getContent($entity, $fileReference));
    }

    public function testSupports(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->times(3)->andReturn($fileInfo);

        $fileInfo->expects('getNormalizedMimeType')->andReturn('text/plain');
        self::assertTrue($this->extractor->supports($entity));

        $fileInfo->expects('getNormalizedMimeType')->andReturn('application/pdf');
        self::assertTrue($this->extractor->supports($entity));

        $fileInfo->expects('getNormalizedMimeType')->andReturn('');
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
