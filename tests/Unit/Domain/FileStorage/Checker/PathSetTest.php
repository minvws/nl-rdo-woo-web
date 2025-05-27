<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\FileStorage\Checker;

use App\Domain\FileStorage\Checker\FileStorageType;
use App\Domain\FileStorage\Checker\PathSet;
use App\Tests\Unit\UnitTestCase;

class PathSetTest extends UnitTestCase
{
    public function testMatching(): void
    {
        $pathSet = new PathSet(
            $name = 'foo',
            $type = FileStorageType::DOCUMENT,
            [
                $pathA = '/foo/bar' => 'path_a_uuid',
                $pathB = '/foo/baz' => 'path_b_uuid',
                $pathC = '/foo/foo' => 'path_c_uuid',
            ]
        );

        self::assertEquals($name, $pathSet->name);
        self::assertEquals($type, $pathSet->fileStorageType);

        self::assertFalse($pathSet->matches(FileStorageType::DOCUMENT, '/some/path', 123));
        self::assertFalse($pathSet->matches(FileStorageType::BATCH, $pathA, 123));

        self::assertTrue($pathSet->matches(FileStorageType::DOCUMENT, $pathA, 100));
        self::assertTrue($pathSet->matches(FileStorageType::DOCUMENT, $pathC, 200));

        self::assertEquals(2, $pathSet->totalCount);
        self::assertEquals(300, $pathSet->totalSize);
        self::assertEquals([$pathB => 'path_b_uuid'], $pathSet->getRemainingPaths());
    }
}
