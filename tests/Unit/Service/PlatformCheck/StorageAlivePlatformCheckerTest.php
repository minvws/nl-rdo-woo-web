<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\PlatformCheck;

use Shared\Service\PlatformCheck\StorageAlivePlatformChecker;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\Tests\Unit\UnitTestCase;

class StorageAlivePlatformCheckerTest extends UnitTestCase
{
    public function testChecker(): void
    {
        $entityStorage = \Mockery::mock(EntityStorageService::class);
        $entityStorage->expects('isAlive')->andReturnTrue();

        $thumbStorage = \Mockery::mock(ThumbnailStorageService::class);
        $thumbStorage->expects('isAlive')->andReturnFalse();

        $checker = new StorageAlivePlatformChecker(
            $entityStorage,
            $thumbStorage,
        );

        $results = $checker->getResults();

        self::assertCount(2, $results);
        self::assertTrue($results[0]->successful);
        self::assertFalse($results[1]->successful);
    }
}
