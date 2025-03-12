<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command\ProductionReportProcessRunCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Handler\ProductionReportProcessRunHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRunRepository;
use App\Service\Inventory\InventoryRunProcessor;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class ProductionReportProcessRunHandlerTest extends UnitTestCase
{
    private ProductionReportProcessRunRepository&MockInterface $productionReportProcessRunRepository;
    private LoggerInterface&MockInterface $logger;
    private InventoryRunProcessor&MockInterface $inventoryRunProcessor;
    private EntityManagerInterface&MockInterface $entityManager;
    private ProductionReportProcessRunHandler $handler;

    public function setUp(): void
    {
        $this->productionReportProcessRunRepository = \Mockery::mock(ProductionReportProcessRunRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->inventoryRunProcessor = \Mockery::mock(InventoryRunProcessor::class);
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);

        $this->handler = new ProductionReportProcessRunHandler(
            $this->productionReportProcessRunRepository,
            $this->logger,
            $this->inventoryRunProcessor,
            $this->entityManager,
        );
    }

    public function testInvokeLogsWarningWhenRunIsNotFound(): void
    {
        $message = new ProductionReportProcessRunCommand(
            $processRunId = Uuid::v6(),
        );

        $this->productionReportProcessRunRepository->expects('find')->with($processRunId)->andReturn(null);

        $this->logger->expects('warning');

        $this->handler->__invoke($message);
    }

    public function testInvokeLogsWarningWhenRunIsNotPendingOrConfirmed(): void
    {
        $message = new ProductionReportProcessRunCommand(
            $processRunId = Uuid::v6(),
        );

        $run = \Mockery::mock(ProductionReportProcessRun::class);
        $run->expects('isPending')->andReturnFalse();
        $run->expects('isConfirmed')->andReturnFalse();

        $this->productionReportProcessRunRepository->expects('find')->with($processRunId)->andReturn($run);

        $this->logger->expects('warning');

        $this->handler->__invoke($message);
    }

    public function testInvokeSuccessful(): void
    {
        $message = new ProductionReportProcessRunCommand(
            $processRunId = Uuid::v6(),
        );

        $run = \Mockery::mock(ProductionReportProcessRun::class);
        $run->expects('isPending')->andReturnTrue();

        $this->productionReportProcessRunRepository->expects('find')->with($processRunId)->andReturn($run);

        $this->inventoryRunProcessor->expects('process')->with($run);
        $this->entityManager->expects('clear');

        $this->handler->__invoke($message);
    }

    public function testInvokeLogsErrorIfProcessThrowsAnException(): void
    {
        $message = new ProductionReportProcessRunCommand(
            $processRunId = Uuid::v6(),
        );

        $run = \Mockery::mock(ProductionReportProcessRun::class);
        $run->expects('isPending')->andReturnTrue();

        $this->productionReportProcessRunRepository->expects('find')->with($processRunId)->andReturn($run);

        $this->inventoryRunProcessor->expects('process')->with($run)->andThrows(new \RuntimeException('oops'));

        $this->logger->expects('error');

        $this->handler->__invoke($message);
    }
}
