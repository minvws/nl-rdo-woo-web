<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Shared\Command\BatchDownloadRefresh;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class BatchDownloadRefreshTest extends UnitTestCase
{
    public function testRun(): void
    {
        $dossierA = \Mockery::mock(WooDecision::class);
        $dossierB = \Mockery::mock(WooDecision::class);
        $wooDecisionRepository = \Mockery::mock(WooDecisionRepository::class);
        $wooDecisionRepository->expects('getPubliclyAvailable')->andReturn([$dossierA, $dossierB]);

        $batchDownloadService = \Mockery::mock(BatchDownloadService::class);

        $batchDownloadService->expects('refresh')->with(\Mockery::on(
            static function (BatchDownloadScope $scope) use ($dossierA): bool {
                return $scope->wooDecision === $dossierA && $scope->inquiry === null;
            }
        ));

        $batchDownloadService->expects('refresh')->with(\Mockery::on(
            static function (BatchDownloadScope $scope) use ($dossierB): bool {
                return $scope->wooDecision === $dossierB && $scope->inquiry === null;
            }
        ));

        $command = new BatchDownloadRefresh($wooDecisionRepository, $batchDownloadService);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals(0, $commandTester->getStatusCode());
    }
}
