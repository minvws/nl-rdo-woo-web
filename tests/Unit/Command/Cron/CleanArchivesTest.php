<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Cron;

use Mockery;
use Shared\Command\Cron\CleanArchives;
use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Shared\Domain\Publication\BatchDownload\BatchDownloadRepository;
use Shared\Domain\Publication\BatchDownload\BatchDownloadStorage;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanArchivesTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $batchDownload = Mockery::mock(BatchDownload::class);

        $batchDownloadRepository = Mockery::mock(BatchDownloadRepository::class);
        $batchDownloadRepository->expects('findExpiredBatchDownloads')
            ->andReturn([$batchDownload]);
        $batchDownloadRepository->expects('remove')
            ->with($batchDownload);

        $batchDownloadStorage = Mockery::mock(BatchDownloadStorage::class);
        $batchDownloadStorage->expects('removeFileForBatch')
            ->with($batchDownload);

        $command = new CleanArchives($batchDownloadRepository, $batchDownloadStorage);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals($command::SUCCESS, $commandTester->getStatusCode());
    }
}
