<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\FileStorage\Checker\PathSetFactory;

use App\Domain\FileStorage\Checker\FileStorageType;
use App\Domain\FileStorage\Checker\PathSet;
use App\Domain\FileStorage\Checker\PathSetFactory\BatchDownloadPathSetsFactory;
use App\Domain\Publication\BatchDownload\BatchDownload;
use App\Domain\Publication\BatchDownload\BatchDownloadRepository;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class BatchDownloadPathSetsFactoryTest extends UnitTestCase
{
    public function testGetPathSets(): void
    {
        $factory = new BatchDownloadPathSetsFactory(
            $repository = \Mockery::mock(BatchDownloadRepository::class),
            $entityManager = \Mockery::mock(EntityManagerInterface::class),
        );

        $repository->expects('findAll')->andReturn([
            $batchDownloadA = \Mockery::mock(BatchDownload::class),
            $batchDownloadB = \Mockery::mock(BatchDownload::class),
        ]);

        $batchDownloadA->expects('getFilename')->andReturn($pathA = 'foo/bar');
        $batchDownloadA->expects('getId')->andReturn($uuidA = Uuid::v6());

        $batchDownloadB->expects('getFilename')->andReturn($pathB = 'foo/baz');
        $batchDownloadB->expects('getId')->andReturn($uuidB = Uuid::v6());

        $entityManager->expects('detach')->with($batchDownloadA);
        $entityManager->expects('detach')->with($batchDownloadB);

        self::assertEquals(
            [
                new PathSet(
                    'BatchDownload',
                    FileStorageType::BATCH,
                    [
                        '/' . $pathA => $uuidA->toRfc4122(),
                        '/' . $pathB => $uuidB->toRfc4122(),
                    ],
                ),
            ],
            iterator_to_array($factory->getPathSets(), false),
        );
    }
}
