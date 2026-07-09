<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Exception\TranslatableException;
use Shared\Service\DossierService;
use Shared\Service\Inventory\InventoryChangeset;
use Shared\Service\Inventory\InventoryComparator;
use Shared\Service\Inventory\InventoryRunProcessor;
use Shared\Service\Inventory\InventoryService;
use Shared\Service\Inventory\InventoryUpdater;
use Shared\Service\Inventory\Progress\ProgressUpdater;
use Shared\Service\Inventory\Progress\RunProgress;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;
use Shared\Service\Logging\LoggingHelper;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class InventoryRunProcessorTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private ManagerRegistry&MockInterface $managerRegistry;
    private LoggerInterface&MockInterface $logger;
    private InventoryComparator&MockInterface $inventoryComparator;
    private InventoryUpdater&MockInterface $inventoryUpdater;
    private InventoryService&MockInterface $inventoryService;
    private DossierService&MockInterface $dossierService;
    private InventoryRunProcessor $runProcessor;
    private ProductionReportProcessRun&MockInterface $run;
    private InventoryReaderInterface&MockInterface $reader;
    private WooDecision&MockInterface $dossier;
    private ProgressUpdater&MockInterface $progressUpdater;

    protected function setUp(): void
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->managerRegistry = Mockery::mock(ManagerRegistry::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $loggingHelper = Mockery::mock(LoggingHelper::class);
        $this->inventoryComparator = Mockery::mock(InventoryComparator::class);
        $this->inventoryUpdater = Mockery::mock(InventoryUpdater::class);
        $this->inventoryService = Mockery::mock(InventoryService::class);
        $this->dossierService = Mockery::mock(DossierService::class);
        $this->progressUpdater = Mockery::mock(ProgressUpdater::class);

        $this->runProcessor = new InventoryRunProcessor(
            $this->entityManager,
            $this->managerRegistry,
            $this->logger,
            $loggingHelper,
            $this->inventoryComparator,
            $this->inventoryUpdater,
            $this->inventoryService,
            $this->dossierService,
            $this->progressUpdater,
        );

        $this->dossier = Mockery::mock(WooDecision::class);
        $this->run = Mockery::mock(ProductionReportProcessRun::class);
        $this->run->expects('startComparing');
        $this->run->allows('getDossier')->andReturn($this->dossier);
        $this->run->allows('getId')->andReturn(Uuid::v6());

        $loggingHelper->expects('disableAll');

        $this->reader = Mockery::mock(InventoryReaderInterface::class);
        $this->reader->allows('getCount')->andReturn(50);

        $this->inventoryService->expects('getReader')->with($this->run)->andReturn($this->reader);
        $this->inventoryService->expects('cleanupTmpFile')->with($this->run);

        parent::setUp();
    }

    public function testProcessReturnsEarlyWithErrorIfThereAreNoChanges(): void
    {
        $changeset = Mockery::mock(InventoryChangeset::class);
        $changeset->expects('hasNoChanges')->andReturnTrue();
        $this->entityManager->expects('isOpen')->andReturnTrue();
        $this->logger->expects('error');

        $this->run->expects('addGenericException');
        $this->run->expects('fail');
        $this->run->expects('isFinal')->andReturnTrue();
        $this->run->expects('isPending')->andReturnTrue();

        $this->entityManager->expects('persist')->with($this->run)->times(2);
        $this->entityManager->expects('flush')->times(2);

        $this->inventoryComparator->expects('determineChangeset')->andReturn($changeset);

        $this->runProcessor->process($this->run);
    }

    public function testProcessReturnsCatchesExceptionAndFailsTheRun(): void
    {
        $this->run->expects('addGenericException');
        $this->run->expects('fail');
        $this->run->expects('isFinal')->andReturnTrue();
        $this->run->expects('isPending')->andReturnTrue();
        $this->entityManager->expects('isOpen')->andReturnTrue();
        $this->entityManager->expects('persist')->with($this->run)->times(2);
        $this->entityManager->expects('flush')->times(2);
        $this->logger->expects('error')
            ->with(
                // @phpcs:ignore Generic.Files.LineLength.TooLong
                'ProductionReportProcessRun failed. See the ProductionReportProcessRun table for more details on the failure including the exception.',
                Mockery::any(),
            );
        $this->inventoryComparator->expects('determineChangeset')->andThrows(new RuntimeException('oops'));

        $this->runProcessor->process($this->run);
    }

    public function testProcessFinishesSuccessfully(): void
    {
        $changeset = Mockery::mock(InventoryChangeset::class);
        $changeset->expects('hasNoChanges')->andReturnFalse();
        $changeset->expects('getResultingTotalDocumentCount')->andReturn(234);

        $this->run->expects('hasErrors')->andReturnFalse();
        $this->run->expects('isFinal')->andReturnTrue();
        $this->run->expects('isPending')->andReturnTrue();
        $this->run->expects('isConfirmed')->andReturnTrue();
        $this->run->expects('setChangeset');
        $this->run->expects('startUpdating');
        $this->run->expects('getChangeset')->andReturn($changeset);

        $this->inventoryService->expects('removeInventories')->with($this->dossier);

        $this->inventoryUpdater
            ->expects('applyChangesetToDatabase')
            ->with($this->run, $this->dossier, $this->reader, $changeset, Mockery::type(RunProgress::class));

        $this->inventoryService->expects('storeProductionReport')->with($this->run);

        $this->dossierService->expects('validateCompletion')->with($this->dossier);

        $this->run->expects('finish');

        $this->inventoryComparator->expects('determineChangeset')->andReturn($changeset);

        $this->inventoryUpdater
            ->expects('sendMessagesForChangeset')
            ->with($changeset, $this->dossier, Mockery::type(RunProgress::class));

        $this->progressUpdater->expects('updateProgressForRun')->times(2);

        $this->entityManager->expects('isOpen')->andReturnTrue();
        $this->entityManager->expects('persist')->with($this->run)->times(3);
        $this->entityManager->expects('persist')->with($this->dossier)->times(1);
        $this->entityManager->expects('flush')->times(5);
        $this->entityManager->expects('refresh')->with($this->dossier);

        $this->runProcessor->process($this->run);
    }

    public function testProcessAddsExceptionToResultWhenMaxDocumentsIsExceeded(): void
    {
        $changeset = Mockery::mock(InventoryChangeset::class);
        $changeset->expects('hasNoChanges')->andReturnFalse();
        $changeset->expects('getResultingTotalDocumentCount')->andReturn(InventoryRunProcessor::MAX_DOCUMENTS + 1);

        $this->run->expects('hasErrors')->andReturnTrue();
        $this->run->expects('isFinal')->andReturnTrue();
        $this->run->expects('isPending')->andReturnTrue();
        $this->run->expects('isConfirmed')->andReturnFalse();
        $this->run->expects('addGenericException');
        $this->run->expects('fail');
        $this->entityManager->expects('isOpen')->andReturnTrue();

        $this->entityManager->expects('persist')->with($this->run)->times(2);
        $this->entityManager->expects('flush')->times(2);

        $this->inventoryComparator->expects('determineChangeset')->andReturn($changeset);

        $this->runProcessor->process($this->run);
    }

    public function testProcessFinalizesRunWithFreshManagerWhenEntityManagerIsClosed(): void
    {
        $changeset = Mockery::mock(InventoryChangeset::class);
        $changeset->expects('hasNoChanges')->andReturnFalse();
        $changeset->expects('getResultingTotalDocumentCount')->andReturn(10);

        $this->run->expects('hasErrors')->andReturnFalse();
        $this->run->expects('isFinal')->andReturnTrue();
        $this->run->expects('isPending')->andReturnTrue();
        $this->run->expects('isConfirmed')->andReturnTrue();
        $this->run->expects('setChangeset');
        $this->run->expects('startUpdating');
        $this->run->expects('getChangeset')->andReturn($changeset);
        $this->entityManager->expects('isOpen')->andReturnFalse();

        $this->entityManager->expects('persist')->with($this->run)->times(2);
        $this->entityManager->expects('flush')->times(4);

        $this->inventoryService->expects('removeInventories')->with($this->dossier);

        $this->inventoryUpdater
            ->expects('applyChangesetToDatabase')
            ->with($this->run, $this->dossier, $this->reader, $changeset, Mockery::type(RunProgress::class));

        $this->inventoryService->expects('storeProductionReport')->with($this->run);

        $this->dossierService->expects('validateCompletion')->with($this->dossier);

        $this->run->expects('finish');

        $this->inventoryComparator->expects('determineChangeset')->andReturn($changeset);

        $this->inventoryUpdater
            ->expects('sendMessagesForChangeset')
            ->with($changeset, $this->dossier, Mockery::type(RunProgress::class));

        $this->progressUpdater->expects('updateProgressForRun')->times(2);

        $this->entityManager->expects('persist')->with($this->dossier);
        $this->entityManager->expects('refresh')->with($this->dossier);

        $freshManager = Mockery::mock(EntityManagerInterface::class);
        $this->managerRegistry->expects('resetManager')
            ->andReturn($freshManager);

        $reloadedRun = Mockery::mock(ProductionReportProcessRun::class);
        $reloadedRun->expects('addGenericException');
        $reloadedRun->expects('fail');

        $freshManager
            ->expects('find')
            ->with(ProductionReportProcessRun::class, Mockery::type(Uuid::class))
            ->andReturn($reloadedRun);
        $freshManager->expects('flush');

        $this->runProcessor->process($this->run);
    }

    public function testProcessAddsExceptionToResultWhenThereAreNoChanges(): void
    {
        $changeset = Mockery::mock(InventoryChangeset::class);
        $changeset->expects('hasNoChanges')->andReturnTrue();
        $changeset->expects('getResultingTotalDocumentCount')->andReturn(10);

        $this->run->expects('isPending')->andReturnTrue();
        $this->run->expects('hasNoErrors')->andReturnTrue();
        $this->run->expects('hasErrors')->andReturnTrue();
        $this->run->expects('addGenericException');
        $this->run->expects('fail');
        $this->run->expects('isConfirmed')->andReturnFalse();
        $this->run->expects('isFinal')->andReturnTrue();

        $this->entityManager->expects('isOpen')->andReturnTrue();
        $this->entityManager->expects('persist')->with($this->run)->times(2);
        $this->entityManager->expects('flush')->times(2);

        $this->inventoryComparator->expects('determineChangeset')->andReturn($changeset);

        $this->runProcessor->process($this->run);
    }

    public function testProcessFailsWhenNoChangesetIsAvailableDuringUpdate(): void
    {
        $changeset = Mockery::mock(InventoryChangeset::class);
        $changeset->expects('hasNoChanges')->andReturnFalse();
        $changeset->expects('getResultingTotalDocumentCount')->andReturn(10);

        $this->run->expects('isPending')->andReturnTrue();
        $this->run->expects('hasErrors')->andReturnFalse();
        $this->run->expects('setChangeset');
        $this->run->expects('isConfirmed')->andReturnTrue();
        $this->run->expects('startUpdating');
        $this->run->expects('getChangeset')->andReturnNull();
        $this->run->expects('addGenericException');
        $this->run->expects('fail');
        $this->run->expects('isFinal')->andReturnTrue();

        $this->entityManager->expects('isOpen')->andReturnTrue();
        $this->entityManager->expects('persist')
            ->with($this->run)
            ->times(3);
        $this->entityManager->expects('flush')->times(3);

        $this->progressUpdater->expects('updateProgressForRun');

        $this->logger->expects('error');

        $this->inventoryComparator->expects('determineChangeset')->andReturn($changeset);

        $this->runProcessor->process($this->run);
    }

    public function testProcessDoesNotFailWhenRunCannotBeFoundWithFreshManager(): void
    {
        $this->run->expects('isPending')->andReturnTrue();
        $this->run->expects('addGenericException');
        $this->run->expects('fail');
        $this->run->expects('isFinal')->andReturnTrue();

        $this->entityManager->expects('isOpen')->andReturnFalse();
        $this->entityManager->expects('persist')->with($this->run);
        $this->entityManager->expects('flush');

        $this->logger->expects('error');

        $this->inventoryComparator->expects('determineChangeset')->andThrows(new RuntimeException('oops'));

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('find')
            ->with(ProductionReportProcessRun::class, Mockery::type(Uuid::class))
            ->andReturnNull();

        $this->managerRegistry->expects('resetManager')->andReturn($entityManager);

        $this->runProcessor->process($this->run);
    }

    public function testProcessFinalizesRunWithCaughtExceptionWhenEntityManagerIsClosed(): void
    {
        $this->run->expects('isPending')
            ->andReturnTrue();
        $this->run->expects('addGenericException');
        $this->run->expects('fail');
        $this->run->expects('isFinal')
            ->andReturnTrue();

        $this->entityManager->expects('isOpen')
            ->andReturnFalse();
        $this->entityManager->expects('persist')
            ->with($this->run);
        $this->entityManager->expects('flush');

        $this->logger->expects('error');

        $this->inventoryComparator->expects('determineChangeset')
            ->andThrows(new RuntimeException('some error'));

        $freshManager = Mockery::mock(EntityManagerInterface::class);
        $this->managerRegistry->expects('resetManager')
            ->andReturn($freshManager);

        $reloadedRun = Mockery::mock(ProductionReportProcessRun::class);
        $reloadedRun->expects('addGenericException')
            ->with(Mockery::on(static function (TranslatableException $exception): bool {
                return $exception->getMessage() === 'Uncaught exception during inventory processing: some error';
            }));
        $reloadedRun->expects('fail');

        $freshManager
            ->expects('find')
            ->with(ProductionReportProcessRun::class, Mockery::type(Uuid::class))
            ->andReturn($reloadedRun);
        $freshManager->expects('flush');

        $this->runProcessor->process($this->run);
    }
}
