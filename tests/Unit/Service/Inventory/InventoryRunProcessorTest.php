<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\ProductionReportProcessRun;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Service\DossierService;
use App\Service\Inventory\InventoryChangeset;
use App\Service\Inventory\InventoryComparator;
use App\Service\Inventory\InventoryRunProcessor;
use App\Service\Inventory\InventoryService;
use App\Service\Inventory\InventoryUpdater;
use App\Service\Inventory\Progress\ProgressUpdater;
use App\Service\Inventory\Progress\RunProgress;
use App\Service\Inventory\Reader\InventoryReaderInterface;
use App\Service\Logging\LoggingHelper;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class InventoryRunProcessorTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private LoggingHelper&MockInterface $loggingHelper;
    private InventoryComparator&MockInterface $inventoryComparator;
    private InventoryUpdater&MockInterface $inventoryUpdater;
    private InventoryService $inventoryService;
    private DossierService&MockInterface $dossierService;
    private InventoryRunProcessor $runProcessor;
    private ProductionReportProcessRun&MockInterface $run;
    private InventoryReaderInterface&MockInterface $reader;
    private WooDecision&MockInterface $dossier;
    private ProgressUpdater&MockInterface $progressUpdater;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->loggingHelper = \Mockery::mock(LoggingHelper::class);
        $this->inventoryComparator = \Mockery::mock(InventoryComparator::class);
        $this->inventoryUpdater = \Mockery::mock(InventoryUpdater::class);
        $this->inventoryService = \Mockery::mock(InventoryService::class);
        $this->dossierService = \Mockery::mock(DossierService::class);
        $this->progressUpdater = \Mockery::mock(ProgressUpdater::class);

        $this->runProcessor = new InventoryRunProcessor(
            $this->entityManager,
            $this->loggingHelper,
            $this->inventoryComparator,
            $this->inventoryUpdater,
            $this->inventoryService,
            $this->dossierService,
            $this->progressUpdater,
        );

        $this->dossier = \Mockery::mock(WooDecision::class);

        $this->run = \Mockery::mock(ProductionReportProcessRun::class);
        $this->run->expects('startComparing');
        $this->run->shouldReceive('getDossier')->andReturn($this->dossier);

        $this->loggingHelper->expects('disableAll');

        $this->reader = \Mockery::mock(InventoryReaderInterface::class);

        $this->reader->shouldReceive('getCount')->andReturn(50);

        $this->inventoryService->expects('getReader')->with($this->run)->andReturn($this->reader);
        $this->inventoryService->expects('cleanupTmpFile')->with($this->run);

        // The next conditions are very important: in all cases (even when exceptions occur) the run must be updated at the start and finish!
        $this->entityManager->expects('persist')->with($this->run)->atLeast()->times(2);
        $this->entityManager->expects('flush')->atLeast()->times(2);

        parent::setUp();
    }

    public function testProcessReturnsEarlyWithErrorIfThereAreNoChanges(): void
    {
        $changeset = \Mockery::mock(InventoryChangeset::class);
        $changeset->shouldReceive('hasChanges')->andReturnTrue();

        $this->run->expects('addGenericException');
        $this->run->shouldReceive('hasErrors')->andReturnTrue();
        $this->run->expects('fail');
        $this->run->shouldReceive('isFinal')->andReturnTrue();
        $this->run->shouldReceive('isPending')->andReturnTrue();
        $this->run->shouldReceive('isConfirmed')->andReturnFalse();

        $this->inventoryComparator->expects('determineChangeset')->andReturn($changeset);

        $this->runProcessor->process($this->run);
    }

    public function testProcessReturnsCatchesExceptionAndFailsTheRun(): void
    {
        $this->run->expects('addGenericException');
        $this->run->shouldReceive('hasErrors')->andReturnTrue();
        $this->run->expects('fail');
        $this->run->shouldReceive('isFinal')->andReturnTrue();
        $this->run->shouldReceive('isPending')->andReturnTrue();
        $this->run->shouldReceive('isConfirmed')->andReturnTrue();

        $this->inventoryComparator->expects('determineChangeset')->andThrows(new \RuntimeException('oops'));

        $this->runProcessor->process($this->run);
    }

    public function testProcessFinishesSuccessfully(): void
    {
        $changeset = \Mockery::mock(InventoryChangeset::class);
        $changeset->shouldReceive('hasChanges')->andReturnFalse();

        $this->run->shouldReceive('hasErrors')->andReturnFalse();
        $this->run->shouldReceive('isFinal')->andReturnTrue();
        $this->run->shouldReceive('isPending')->andReturnTrue();
        $this->run->shouldReceive('isConfirmed')->andReturnTrue();
        $this->run->shouldReceive('setChangeset');
        $this->run->shouldReceive('startUpdating');
        $this->run->shouldReceive('getChangeset')->andReturn($changeset);

        $this->inventoryService->expects('removeInventories')->with($this->dossier);

        $this->inventoryUpdater
            ->expects('applyChangesetToDatabase')
            ->with($this->dossier, $this->reader, $changeset, \Mockery::type(RunProgress::class));

        $this->inventoryService->expects('storeProductionReport')->with($this->run);

        $this->dossierService->expects('validateCompletion')->with($this->dossier);

        $this->run->expects('finish');

        $this->inventoryComparator->expects('determineChangeset')->andReturn($changeset);

        $this->inventoryUpdater
            ->expects('sendMessagesForChangeset')
            ->with($changeset, $this->dossier, \Mockery::type(RunProgress::class));

        $this->progressUpdater->expects('updateProgressForRun')->twice();

        $this->entityManager->expects('persist')->with($this->dossier);
        $this->entityManager->expects('refresh')->with($this->dossier);

        $this->runProcessor->process($this->run);
    }
}
