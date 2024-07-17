<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\PlatformCheck;

use App\Service\PlatformCheck\StorageAlivePlatformChecker;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class StorageAlivePlatformCheckerTest extends MockeryTestCase
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
