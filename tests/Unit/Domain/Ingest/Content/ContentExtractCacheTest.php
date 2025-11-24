<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content;

use Mockery\MockInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Shared\Domain\Ingest\Content\ContentExtractCache;
use Shared\Domain\Ingest\Content\ContentExtractCollection;
use Shared\Domain\Ingest\Content\ContentExtractOptions;
use Shared\Domain\Ingest\Content\ContentExtractService;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Unit\UnitTestCase;

class ContentExtractCacheTest extends UnitTestCase
{
    private CacheItemPoolInterface&MockInterface $cacheItemPool;
    private ContentExtractService&MockInterface $contentExtractService;
    private EntityWithFileInfo&MockInterface $entity;
    private FileInfo&MockInterface $fileInfo;
    private ContentExtractCache $contentExtractCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contentExtractService = \Mockery::mock(ContentExtractService::class);
        $this->cacheItemPool = \Mockery::mock(CacheItemPoolInterface::class);

        $this->fileInfo = \Mockery::mock(FileInfo::class);
        $this->fileInfo->shouldReceive('getHash')->andReturn('testhash');

        $this->entity = \Mockery::mock(EntityWithFileInfo::class);
        $this->entity->shouldReceive('getFileInfo')->andReturn($this->fileInfo);

        $this->contentExtractCache = new ContentExtractCache(
            $this->contentExtractService,
            $this->cacheItemPool,
        );
    }

    public function testGetCombinedExtractsWithCacheMiss(): void
    {
        $pageNr = 123;
        $content = "lorem ipsum tika\nlorem ipsum tesseract";

        $cacheItem = \Mockery::mock(CacheItemInterface::class);
        $cacheItem->shouldReceive('isHit')->andReturn(false);
        $cacheItem->shouldReceive('set')->with($content)->andReturnSelf();
        $cacheItem->shouldReceive('get')->andReturn($content);
        $this->cacheItemPool->expects('getItem')->with('testhash-' . $pageNr)->andReturn($cacheItem);
        $this->cacheItemPool->expects('save')->with($cacheItem);

        $collection = \Mockery::mock(ContentExtractCollection::class);
        $collection->shouldReceive('getCombinedContent')->andReturn($content);

        $this->contentExtractService
            ->expects('getExtracts')
            ->with($this->entity, \Mockery::on(
                static function (ContentExtractOptions $options) use ($pageNr): bool {
                    self::assertEquals($pageNr, $options->getPageNumber());

                    return true;
                }
            ))
            ->andReturn($collection);

        $options = new ContentExtractOptions();
        $options->withPageNumber($pageNr);

        $this->contentExtractCache->getCombinedExtracts($this->entity, $options);
    }

    public function testGetCombinedExtractsWithCacheHit(): void
    {
        $pageNr = 123;
        $content = "lorem ipsum tika\nlorem ipsum tesseract";

        $cacheItem = \Mockery::mock(CacheItemInterface::class);
        $cacheItem->shouldReceive('isHit')->andReturnTrue();
        $cacheItem->shouldReceive('get')->andReturn($content);
        $this->cacheItemPool->expects('getItem')->with('testhash-' . $pageNr)->andReturn($cacheItem);

        $options = new ContentExtractOptions();
        $options->withPageNumber($pageNr);

        $this->contentExtractCache->getCombinedExtracts($this->entity, $options);
    }

    public function testHasCacheReturnsTrueForHit(): void
    {
        $pageNr = 123;

        $cacheItem = \Mockery::mock(CacheItemInterface::class);
        $cacheItem->shouldReceive('isHit')->andReturnTrue();

        $this->cacheItemPool->expects('getItem')->with('testhash-' . $pageNr)->andReturn($cacheItem);

        self::assertTrue($this->contentExtractCache->hasCache($this->entity, $pageNr));
    }

    public function testHasCacheReturnsFalseForMisse(): void
    {
        $pageNr = 123;

        $cacheItem = \Mockery::mock(CacheItemInterface::class);
        $cacheItem->shouldReceive('isHit')->andReturnFalse();

        $this->cacheItemPool->expects('getItem')->with('testhash-' . $pageNr)->andReturn($cacheItem);

        self::assertFalse($this->contentExtractCache->hasCache($this->entity, $pageNr));
    }
}
