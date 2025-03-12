<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\InitiateProductionReportUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Handler\InitiateProductionReportUpdateHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRunRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Domain\Publication\FileInfo;
use App\Exception\ProductionReportUpdaterException;
use App\Service\Storage\EntityStorageService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

class InitiateProductionReportUpdateHandlerTest extends UnitTestCase
{
    private ProductionReportProcessRunRepository&MockInterface $processRunRepository;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private EntityStorageService&MockInterface $entityStorage;
    private LoggerInterface&MockInterface $logger;
    private ProductionReportDispatcher&MockInterface $dispatcher;
    private InitiateProductionReportUpdateHandler $handler;

    public function setUp(): void
    {
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->processRunRepository = \Mockery::mock(ProductionReportProcessRunRepository::class);
        $this->entityStorage = \Mockery::mock(EntityStorageService::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->dispatcher = \Mockery::mock(ProductionReportDispatcher::class);

        $this->handler = new InitiateProductionReportUpdateHandler(
            $this->dossierWorkflowManager,
            $this->processRunRepository,
            $this->entityStorage,
            $this->logger,
            $this->dispatcher,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $oldRun = \Mockery::mock(ProductionReportProcessRun::class);
        $oldRun->shouldReceive('isNotFinal')->andReturnFalse();

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getProcessRun')->andReturn($oldRun);

        $this->dossierWorkflowManager->expects('applyTransition')->with($wooDecision, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $this->entityStorage->expects('deleteAllFilesForEntity')->with($oldRun);
        $this->processRunRepository->expects('remove')->with($oldRun, true);

        $newRun = \Mockery::mock(ProductionReportProcessRun::class);
        $newRun->shouldReceive('getId')->andReturn(Uuid::v6());
        $newRun->shouldReceive('getFileInfo')->andReturn(new FileInfo());

        $this->processRunRepository->expects('create')->with($wooDecision)->andReturn($newRun);

        $this->processRunRepository->expects('save')->with($newRun, true);

        $upload = \Mockery::mock(UploadedFile::class);
        $upload->shouldReceive('getClientOriginalName')->andReturn('foo.pdf');

        $this->entityStorage->expects('storeEntity')->andReturnTrue();

        $this->dispatcher->expects('dispatchProductionReportProcessRunCommand')->with($newRun->getId());

        $this->handler->__invoke(
            new InitiateProductionReportUpdateCommand($wooDecision, $upload)
        );
    }

    public function testInvokeFailsWhenUploadCannotBeStored(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getProcessRun')->andReturnNull();
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->dossierWorkflowManager->expects('applyTransition')->with($wooDecision, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $run = \Mockery::mock(ProductionReportProcessRun::class);
        $run->shouldReceive('getId')->andReturn(Uuid::v6());
        $run->shouldReceive('getFileInfo')->andReturn(new FileInfo());

        $this->processRunRepository->expects('create')->with($wooDecision)->andReturn($run);
        $this->processRunRepository->expects('save')->with($run, true);

        $upload = \Mockery::mock(UploadedFile::class);
        $upload->shouldReceive('getClientOriginalName')->andReturn('foo.pdf');

        $this->entityStorage->expects('storeEntity')->andReturnFalse();

        $this->logger->expects('error');
        $run->expects('addGenericException');
        $run->expects('fail');
        $this->processRunRepository->expects('save')->with($run, true);

        $this->expectExceptionObject(ProductionReportUpdaterException::forUploadCannotBeStored());

        $this->handler->__invoke(
            new InitiateProductionReportUpdateCommand($wooDecision, $upload)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $upload = \Mockery::mock(UploadedFile::class);

        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($wooDecision, DossierStatusTransition::UPDATE_PRODUCTION_REPORT)
            ->andThrow(new DossierWorkflowException());

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new InitiateProductionReportUpdateCommand($wooDecision, $upload)
        );
    }

    public function testInvokeThrowsExceptionWhenExistingRunIsNotFinal(): void
    {
        $run = \Mockery::mock(ProductionReportProcessRun::class);
        $run->shouldReceive('isNotFinal')->andReturnTrue();

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getProcessRun')->andReturn($run);
        $upload = \Mockery::mock(UploadedFile::class);

        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($wooDecision, DossierStatusTransition::UPDATE_PRODUCTION_REPORT);

        $this->expectException(ProductionReportUpdaterException::class);

        $this->handler->__invoke(
            new InitiateProductionReportUpdateCommand($wooDecision, $upload)
        );
    }
}
