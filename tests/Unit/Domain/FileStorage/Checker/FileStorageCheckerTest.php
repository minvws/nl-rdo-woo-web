<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\FileStorage\Checker;

use App\Domain\FileStorage\Checker\FileStorageChecker;
use App\Domain\FileStorage\Checker\FileStorageLister;
use App\Domain\FileStorage\Checker\FileStorageType;
use App\Domain\FileStorage\Checker\PathSet;
use App\Domain\FileStorage\Checker\PathSetFactory\PathSetsFactoryInterface;
use App\Tests\Unit\Domain\Upload\IterableToGenerator;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

class FileStorageCheckerTest extends UnitTestCase
{
    use IterableToGenerator;

    private FileStorageChecker $fileStorageChecker;
    private FileStorageLister&MockInterface $fileStorageLister;
    private PathSetsFactoryInterface&MockInterface $pathSetFactoryA;
    private PathSetsFactoryInterface&MockInterface $pathSetFactoryB;

    public function setUp(): void
    {
        $this->fileStorageChecker = new FileStorageChecker(
            $this->fileStorageLister = \Mockery::mock(FileStorageLister::class),
            [
                $this->pathSetFactoryA = \Mockery::mock(PathSetsFactoryInterface::class),
                $this->pathSetFactoryB = \Mockery::mock(PathSetsFactoryInterface::class),
            ],
        );
    }

    public function testCheck(): void
    {
        $this->pathSetFactoryA->expects('getPathSets')->andReturn(
            $this->iterableToGenerator([
                new PathSet(
                    'doc_a',
                    FileStorageType::DOCUMENT,
                    ['/doc/a/1' => 'uuid_a_1', '/doc/a/2' => 'uuid_a_2', '/doc/a/missing' => 'uuid_a_missing'],
                ),
                new PathSet(
                    'batch_a',
                    FileStorageType::BATCH,
                    ['/batch/a/1' => 'uuid_a_1', '/batch/a/2' => 'uuid_a_2', '/batch/a/missing' => 'uuid_a_missing'],
                ),
            ]),
        );

        $this->pathSetFactoryB->expects('getPathSets')->andReturn(
            $this->iterableToGenerator([
                new PathSet(
                    'doc_b',
                    FileStorageType::DOCUMENT,
                    ['/doc/b/1' => 'uuid_b_1', '/doc/b/2' => 'uuid_b_2', 'doc/b/missing' => 'uuid_b_missing'],
                ),
                new PathSet(
                    'batch_b',
                    FileStorageType::BATCH,
                    ['/batch/b/3' => 'uuid_b_3', '/batch/b/4' => 'uuid_b_4', 'batch/b/missing' => 'uuid_b_missing'],
                ),
            ]),
        );

        $this->fileStorageLister->expects('paths')->with(FileStorageType::DOCUMENT)->andReturn(
            $this->iterableToGenerator(
                [
                    '/doc/a/1' => 1,
                    '/doc/a/2' => 10,
                    '/doc/b/1' => 100,
                    '/doc/b/2' => 1000,
                    '/doc/orphan' => 10000,
                ],
            ),
        );

        $this->fileStorageLister->expects('paths')->with(FileStorageType::BATCH)->andReturn(
            $this->iterableToGenerator(
                [
                    '/batch/a/1' => 100000,
                    '/batch/a/2' => 1000000,
                    '/batch/b/3' => 10000000,
                    '/batch/b/4' => 100000000,
                    '/batch/orphan' => 1000000000,
                ],
            ),
        );

        $this->assertMatchesSnapshot(
            $this->fileStorageChecker->check(),
        );
    }
}
