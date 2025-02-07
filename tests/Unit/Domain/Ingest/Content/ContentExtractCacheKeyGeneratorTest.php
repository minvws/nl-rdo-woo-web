<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Content;

use App\Domain\Ingest\Content\ContentExtractCacheKeyGenerator;
use App\Domain\Ingest\Content\ContentExtractOptions;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Publication\EntityWithFileInfo;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Uid\Uuid;

class ContentExtractCacheKeyGeneratorTest extends MockeryTestCase
{
    private ContentExtractCacheKeyGenerator $keyGenerator;

    public function setUp(): void
    {
        $this->keyGenerator = new ContentExtractCacheKeyGenerator();
    }

    public function testWithoutPageNumber(): void
    {
        $options = ContentExtractOptions::create();

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn(Uuid::fromRfc4122('55ae5de9-55f4-3420-b50b-5cde6e07fc5a'));
        $entity->shouldReceive('getFileCacheKey')->andReturn('entitykey');
        $entity->shouldReceive('getFileInfo->getHash')->andReturn('FooBar');

        $cacheKey = $this->keyGenerator->generate(
            ContentExtractorKey::TIKA,
            $entity,
            $options,
        );

        self::assertEquals(
            'tika-entitykey-55ae5de9-55f4-3420-b50b-5cde6e07fc5a-0-FooBar',
            $cacheKey
        );
    }

    public function testWithPageNumber(): void
    {
        $options = ContentExtractOptions::create()->withPageNumber(123);

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn(Uuid::fromRfc4122('55ae5de9-55f4-3420-b50b-5cde6e07fc5a'));
        $entity->shouldReceive('getFileCacheKey')->andReturn('entitykey');
        $entity->shouldReceive('getFileInfo->getHash')->andReturn('FooBar');

        $cacheKey = $this->keyGenerator->generate(
            ContentExtractorKey::TIKA,
            $entity,
            $options,
        );

        self::assertEquals(
            'tika-entitykey-55ae5de9-55f4-3420-b50b-5cde6e07fc5a-123-FooBar',
            $cacheKey
        );
    }

    public function testGenerateThrowsExceptionForMissingHash(): void
    {
        $options = ContentExtractOptions::create()->withPageNumber(123);

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn(Uuid::fromRfc4122('55ae5de9-55f4-3420-b50b-5cde6e07fc5a'));
        $entity->shouldReceive('getFileCacheKey')->andReturn('entitykey');
        $entity->shouldReceive('getFileInfo->getHash')->andReturnNull();

        $this->expectException(\InvalidArgumentException::class);

        $this->keyGenerator->generate(
            ContentExtractorKey::TIKA,
            $entity,
            $options,
        );
    }
}
