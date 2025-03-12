<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\RejectProductionReportUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Handler\RejectProductionReportUpdateHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRunRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Exception\ProductionReportUpdaterException;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class RejectProductionReportUpdateHandlerTest extends UnitTestCase
{
    private ProductionReportProcessRunRepository&MockInterface $processRunRepository;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private LoggerInterface&MockInterface $logger;
    private RejectProductionReportUpdateHandler $handler;

    public function setUp(): void
    {
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->processRunRepository = \Mockery::mock(ProductionReportProcessRunRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);

        $this->handler = new RejectProductionReportUpdateHandler(
            $this->dossierWorkflowManager,
            $this->processRunRepository,
            $this->logger,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $run = \Mockery::mock(ProductionReportProcessRun::class);

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getProcessRun')->andReturn($run);

        $this->dossierWorkflowManager->expects('applyTransition')->with($wooDecision, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $run->expects('reject');
        $this->processRunRepository->expects('save')->with($run, true);

        $this->handler->__invoke(
            new RejectProductionReportUpdateCommand($wooDecision)
        );
    }

    public function testInvokeThrowsExceptionWhenWooDecisionHasNoProcessRun(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getProcessRun')->andReturnNull();

        $this->dossierWorkflowManager->expects('applyTransition')->with($wooDecision, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $this->expectException(ProductionReportUpdaterException::class);

        $this->handler->__invoke(
            new RejectProductionReportUpdateCommand($wooDecision)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);

        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($wooDecision, DossierStatusTransition::UPDATE_PRODUCTION_REPORT)
            ->andThrow(new DossierWorkflowException());

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new RejectProductionReportUpdateCommand($wooDecision)
        );
    }

    public function testInvokeLogsWarningWhenRejectThrowsAnException(): void
    {
        $run = \Mockery::mock(ProductionReportProcessRun::class);

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getProcessRun')->andReturn($run);

        $this->dossierWorkflowManager->expects('applyTransition')->with($wooDecision, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $run->expects('reject')->andThrow(new \RuntimeException('oops'));
        $run->shouldReceive('getId')->andReturn(Uuid::v6());
        $run->shouldReceive('getStatus')->andReturn(ProductionReportProcessRun::STATUS_FINISHED);

        $this->logger->expects('warning');

        $this->handler->__invoke(
            new RejectProductionReportUpdateCommand($wooDecision)
        );
    }
}
