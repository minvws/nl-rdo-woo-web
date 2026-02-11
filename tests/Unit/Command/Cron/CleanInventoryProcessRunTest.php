<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Cron;

use Mockery;
use Shared\Command\Cron\CleanInventoryProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRunRepository;
use Shared\Exception\ProcessInventoryException;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanInventoryProcessRunTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $expiredRun = Mockery::mock(ProductionReportProcessRun::class);
        $expiredRun->expects('addGenericException')
            ->with(Mockery::on(static function (ProcessInventoryException $processInventoryException): bool {
                return $processInventoryException->getMessage() === 'publication.dossier.error.maximum_processing_time_exceeded';
            }));
        $expiredRun->expects('fail');

        $repository = Mockery::mock(ProductionReportProcessRunRepository::class);
        $repository->expects('findExpiredRuns')
            ->andReturn([$expiredRun]);
        $repository->expects('save')
            ->with($expiredRun, true);

        $command = new CleanInventoryProcessRun($repository);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals($command::SUCCESS, $commandTester->getStatusCode());
    }
}
