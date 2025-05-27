<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\FileStorage\Checker;

use App\Domain\FileStorage\Checker\FileStorageType;
use App\Domain\FileStorage\Checker\OrphanedPaths;
use App\Tests\Unit\UnitTestCase;

class OrphanedPathsTest extends UnitTestCase
{
    public function testAdd(): void
    {
        $orphans = new OrphanedPaths();

        self::assertEquals(0, $orphans->totalSize);
        self::assertEquals(0, $orphans->totalCount);
        self::assertEquals([], $orphans->paths);

        $orphans->add(
            $typeA = FileStorageType::DOCUMENT,
            $pathA = '/foo/bar',
            100,
        );

        $orphans->add(
            $typeB = FileStorageType::BATCH,
            $pathB = '/baz/bar',
            200,
        );

        $orphans->add(
            $typeA,
            $pathC = '/baz/bar',
            300,
        );

        self::assertEquals(600, $orphans->totalSize);
        self::assertEquals(3, $orphans->totalCount);
        self::assertEquals(
            [
                $typeA->value => [
                    $pathA,
                    $pathC,
                ],
                $typeB->value => [
                    $pathB,
                ],
            ],
            $orphans->paths,
        );
    }
}
