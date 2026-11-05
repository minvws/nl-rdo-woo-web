<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Cron;

use Mockery;
use Shared\Command\Cron\CleanInventoryProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRunRepository;
use Shared\Service\Import\ImportError;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanInventoryProcessRunTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $expiredRun = Mockery::mock(ProductionReportProcessRun::class);
        $expiredRun->expects('addGenericError')
            ->with(Mockery::on(static function (ImportError $importError): bool {
                return $importError->translationKey === 'publication.dossier.error.maximum_processing_time_exceeded';
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
