<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\FileStorage\Checker;

use App\Domain\FileStorage\Checker\FileStorageCheckResult;
use App\Domain\FileStorage\Checker\OrphanedPaths;
use App\Domain\FileStorage\Checker\PathSet;
use App\Tests\Unit\UnitTestCase;

class FileStorageCheckResultTest extends UnitTestCase
{
    public function testPublicProperties(): void
    {
        $result = new FileStorageCheckResult(
            $orphans = \Mockery::mock(OrphanedPaths::class),
            [
                $pathSetA = \Mockery::mock(PathSet::class),
                $pathSetB = \Mockery::mock(PathSet::class),
            ],
        );

        self::assertEquals($orphans, $result->orphanedPaths);
        self::assertEquals([$pathSetA, $pathSetB], $result->pathSets);
    }
}
