<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\FileStorage\Checker;

use Shared\Domain\FileStorage\Checker\FileStorageCheckResult;
use Shared\Domain\FileStorage\Checker\OrphanedPaths;
use Shared\Domain\FileStorage\Checker\PathSet;
use Shared\Tests\Unit\UnitTestCase;

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
