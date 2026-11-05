<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content;

use Mockery;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Shared\Domain\Ingest\Content\ContentExtractCache;
use Shared\Domain\Ingest\Content\ContentExtractCollection;
use Shared\Domain\Ingest\Content\ContentExtractOptions;
use Shared\Domain\Ingest\Content\ContentExtractService;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\InvalidArgumentException;

use function sprintf;

class ContentExtractCacheTest extends UnitTestCase
{
    public function testGetCombinedExtractsWithCacheMiss(): void
    {
        $pageNr = 123;
        $content = "lorem ipsum tika\nlorem ipsum tesseract";
        $hash = $this->getFaker()->sha256();

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getHash')->andReturn($hash);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $cacheItem = Mockery::mock(CacheItemInterface::class);
        $cacheItem->expects('isHit')->andReturn(false);
        $cacheItem->expects('set')->with($content)->andReturnSelf();
        $cacheItem->expects('get')->andReturn($content);

        $cacheItemPool = Mockery::mock(CacheItemPoolInterface::class);
        $cacheItemPool->expects('getItem')->with(sprintf('%s-%s', $hash, $pageNr))->andReturn($cacheItem);
        $cacheItemPool->expects('save')->with($cacheItem);

        $collection = Mockery::mock(ContentExtractCollection::class);
        $collection->expects('getCombinedContent')->andReturn($content);

        $contentExtractService = Mockery::mock(ContentExtractService::class);
        $contentExtractService
            ->expects('getExtracts')
            ->with($entity, Mockery::on(
                static function (ContentExtractOptions $options) use ($pageNr): bool {
                    self::assertEquals($pageNr, $options->getPageNumber());

                    return true;
                },
            ))
            ->andReturn($collection);

        $options = new ContentExtractOptions();
        $options->withPageNumber($pageNr);

        $contentExtractCache = new ContentExtractCache($contentExtractService, $cacheItemPool);
        $contentExtractCache->getCombinedExtracts($entity, $options);
    }

    public function testGetCombinedExtractsWithCacheHit(): void
    {
        $pageNr = 123;
        $content = "lorem ipsum tika\nlorem ipsum tesseract";
        $hash = $this->getFaker()->sha256();

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getHash')->andReturn($hash);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $cacheItem = Mockery::mock(CacheItemInterface::class);
        $cacheItem->expects('isHit')->andReturnTrue();
        $cacheItem->expects('get')->andReturn($content);

        $cacheItemPool = Mockery::mock(CacheItemPoolInterface::class);
        $cacheItemPool->expects('getItem')->with(sprintf('%s-%s', $hash, $pageNr))->andReturn($cacheItem);

        $options = new ContentExtractOptions();
        $options->withPageNumber($pageNr);

        $contentExtractCache = new ContentExtractCache(Mockery::mock(ContentExtractService::class), $cacheItemPool);
        $contentExtractCache->getCombinedExtracts($entity, $options);
    }

    public function testHasCacheReturnsTrueForHit(): void
    {
        $pageNr = 123;
        $hash = $this->getFaker()->sha256();

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getHash')->andReturn($hash);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $cacheItem = Mockery::mock(CacheItemInterface::class);
        $cacheItem->expects('isHit')->andReturnTrue();

        $cacheItemPool = Mockery::mock(CacheItemPoolInterface::class);
        $cacheItemPool->expects('getItem')->with(sprintf('%s-%s', $hash, $pageNr))->andReturn($cacheItem);

        $contentExtractCache = new ContentExtractCache(Mockery::mock(ContentExtractService::class), $cacheItemPool);

        self::assertTrue($contentExtractCache->hasCache($entity, $pageNr));
    }

    public function testHasCacheReturnsFalseForMisse(): void
    {
        $pageNr = 123;
        $hash = $this->getFaker()->sha256();

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getHash')->andReturn($hash);

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $cacheItem = Mockery::mock(CacheItemInterface::class);
        $cacheItem->expects('isHit')->andReturnFalse();

        $cacheItemPool = Mockery::mock(CacheItemPoolInterface::class);
        $cacheItemPool->expects('getItem')->with(sprintf('%s-%s', $hash, $pageNr))->andReturn($cacheItem);

        $contentExtractCache = new ContentExtractCache(Mockery::mock(ContentExtractService::class), $cacheItemPool);

        self::assertFalse($contentExtractCache->hasCache($entity, $pageNr));
    }

    public function testHasCacheFaislForEntityWithoutHash(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getHash')->andReturnNull();

        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getFileInfo')->andReturn($fileInfo);

        $contentExtractCache = new ContentExtractCache(
            Mockery::mock(ContentExtractService::class),
            Mockery::mock(CacheItemPoolInterface::class),
        );

        $this->expectException(InvalidArgumentException::class);

        $contentExtractCache->hasCache($entity, $this->getFaker()->numberBetween(1, 100));
    }
}
