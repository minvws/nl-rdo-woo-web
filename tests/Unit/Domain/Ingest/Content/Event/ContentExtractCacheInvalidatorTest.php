<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Content\Event;

use App\Domain\Ingest\Content\Event\ContentExtractCacheInvalidator;
use App\Domain\Ingest\Content\Event\EntityFileUpdateEvent;
use App\Domain\Publication\EntityWithFileInfo;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ContentExtractCacheInvalidatorTest extends MockeryTestCase
{
    public function testInvoke(): void
    {
        $invalidator = new ContentExtractCacheInvalidator(
            $cache = \Mockery::mock(TagAwareCacheInterface::class),
            $logger = \Mockery::mock(LoggerInterface::class),
        );

        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn($entityId = Uuid::v6());

        $logger->shouldReceive('info');

        $cache->expects('invalidateTags')->with([$entityId->toRfc4122()]);

        $invalidator->__invoke(
            EntityFileUpdateEvent::forEntity($entity),
        );
    }
}
