<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\ConfirmProductionReportUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Handler\ConfirmProductionReportUpdateHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRunRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Domain\Publication\FileInfo;
use App\Exception\ProductionReportUpdaterException;
use App\Service\HistoryService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class ConfirmProductionReportUpdateHandlerTest extends UnitTestCase
{
    private ProductionReportProcessRunRepository&MockInterface $processRunRepository;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private HistoryService&MockInterface $historyService;
    private ProductionReportDispatcher&MockInterface $dispatcher;
    private ConfirmProductionReportUpdateHandler $handler;

    protected function setUp(): void
    {
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->processRunRepository = \Mockery::mock(ProductionReportProcessRunRepository::class);
        $this->historyService = \Mockery::mock(HistoryService::class);
        $this->dispatcher = \Mockery::mock(ProductionReportDispatcher::class);

        $this->handler = new ConfirmProductionReportUpdateHandler(
            $this->dossierWorkflowManager,
            $this->processRunRepository,
            $this->historyService,
            $this->dispatcher,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $run = \Mockery::mock(ProductionReportProcessRun::class);
        $run->shouldReceive('getId')->andReturn($processRunId = Uuid::v6());
        $run->shouldReceive('getFileInfo')->andReturn(new FileInfo());

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::v6());
        $wooDecision->shouldReceive('getProcessRun')->andReturn($run);

        $this->dossierWorkflowManager->expects('applyTransition')->with($wooDecision, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $run->expects('confirm');
        $this->processRunRepository->expects('save')->with($run, true);

        $this->historyService->expects('addDossierEntry');

        $this->dispatcher->expects('dispatchProductionReportProcessRunCommand')->with($processRunId);

        $this->handler->__invoke(
            new ConfirmProductionReportUpdateCommand($wooDecision)
        );
    }

    public function testInvokeThrowsExceptionWhenWooDecisionHasNoProcessRun(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getProcessRun')->andReturnNull();

        $this->dossierWorkflowManager->expects('applyTransition')->with($wooDecision, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $this->expectException(ProductionReportUpdaterException::class);

        $this->handler->__invoke(
            new ConfirmProductionReportUpdateCommand($wooDecision)
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
            new ConfirmProductionReportUpdateCommand($wooDecision)
        );
    }
}
