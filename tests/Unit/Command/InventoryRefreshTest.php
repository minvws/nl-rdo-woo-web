<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\InventoryRefresh;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Uuid;

class InventoryRefreshTest extends MockeryTestCase
{
    public function testRun(): void
    {
        $dossierA = \Mockery::mock(WooDecision::class);
        $dossierA->expects('getId')->andReturn($dossierIdA = Uuid::v6());
        $dossierB = \Mockery::mock(WooDecision::class);
        $dossierB->expects('getId')->andReturn($dossierIdB = Uuid::v6());
        $wooDecisionRepository = \Mockery::mock(WooDecisionRepository::class);
        $wooDecisionRepository->expects('getPubliclyAvailable')->andReturn([$dossierA, $dossierB]);

        $inquiryA = \Mockery::mock(Inquiry::class);
        $inquiryA->expects('getId')->andReturn($inquiryIdA = Uuid::v6());
        $inquiryB = \Mockery::mock(Inquiry::class);
        $inquiryB->expects('getId')->andReturn($inquiryIdB = Uuid::v6());
        $inquiryRepository = \Mockery::mock(InquiryRepository::class);
        $inquiryRepository->expects('findAll')->andReturn([$inquiryA, $inquiryB]);

        $productionReportDispatcher = \Mockery::mock(ProductionReportDispatcher::class);
        $productionReportDispatcher->expects('dispatchGenerateInventoryCommand')->with($dossierIdA);
        $productionReportDispatcher->expects('dispatchGenerateInventoryCommand')->with($dossierIdB);

        $wooDecisionDispatcher = \Mockery::mock(WooDecisionDispatcher::class);
        $wooDecisionDispatcher->expects('dispatchGenerateInquiryInventoryCommand')->with($inquiryIdA);
        $wooDecisionDispatcher->expects('dispatchGenerateInquiryInventoryCommand')->with($inquiryIdB);

        $command = new InventoryRefresh(
            $wooDecisionRepository,
            $inquiryRepository,
            $productionReportDispatcher,
            $wooDecisionDispatcher,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
