<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Exception\ProcessInventoryException;
use Shared\Service\Inventory\InventoryService;
use Shared\Service\Inventory\Reader\InventoryReaderFactory;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;
use Shared\Service\Storage\EntityStorageService;
use Shared\Tests\Unit\UnitTestCase;

class InventoryServiceTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private EntityStorageService&MockInterface $entityStorageService;
    private InventoryReaderFactory&MockInterface $readerFactory;
    private InventoryService $inventoryService;
    private ProductionReportProcessRun&MockInterface $run;

    protected function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->readerFactory = \Mockery::mock(InventoryReaderFactory::class);
        $this->run = \Mockery::mock(ProductionReportProcessRun::class);

        $this->inventoryService = new InventoryService(
            $this->entityManager,
            $this->entityStorageService,
            $this->readerFactory,
        );

        parent::setUp();
    }

    public function testGetReaderThrowsExceptionWhenUploadIsMissing(): void
    {
        $this->run->shouldReceive('getFileInfo->isUploaded')->andReturnFalse();

        $this->expectException(\RuntimeException::class);

        $this->inventoryService->getReader($this->run);
    }

    public function testGetReaderThrowsExceptionWhenFileCannotBeDownloaded(): void
    {
        $this->run->shouldReceive('getFileInfo->isUploaded')->andReturnTrue();

        $this->entityStorageService->expects('downloadEntity')->with($this->run)->andReturnFalse();

        $this->expectExceptionObject(ProcessInventoryException::forInventoryCannotBeLoadedFromStorage());

        $this->inventoryService->getReader($this->run);
    }

    public function testGetReaderReturnsAnOpenReaderAndSetsTmpFilenameOnRun(): void
    {
        $this->run->shouldReceive('getFileInfo->isUploaded')->andReturnTrue();
        $this->run->shouldReceive('getFileInfo->getMimetype')->andReturn('text/csv');

        $filename = 'tst/123.csv';
        $this->entityStorageService->expects('downloadEntity')->with($this->run)->andReturn($filename);

        $this->run->expects('setTmpFilename')->with($filename);

        $reader = \Mockery::mock(InventoryReaderInterface::class);
        $reader->expects('open')->with($filename);

        $this->readerFactory->expects('create')->andReturn($reader);

        $result = $this->inventoryService->getReader($this->run);

        self::assertSame($reader, $result);
    }

    public function testCleanupTmpFileSkipsWhenThereIsNoTmpFile(): void
    {
        $this->run->expects('getTmpFilename')->andReturnNull();

        $this->inventoryService->cleanupTmpFile($this->run);
    }

    public function testCleanupTmpFileRemovesAndResetsTmpFile(): void
    {
        $filename = 'tst/123.csv';
        $this->run->shouldReceive('getTmpFilename')->andReturn($filename);
        $this->run->expects('setTmpFilename')->with(null);

        $this->entityStorageService->expects('removeDownload')->with($filename);

        $this->inventoryService->cleanupTmpFile($this->run);
    }

    public function testRemoveInventoriesDoesNothingAndReturnsFalseWhenThereAreNoInventories(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->expects('getInventory')->andReturnNull();
        $dossier->expects('getProductionReport')->andReturnNull();

        self::assertFalse(
            $this->inventoryService->removeInventories($dossier)
        );
    }

    public function testRemoveInventoriesRemovesAllInventories(): void
    {
        $inventory = \Mockery::mock(Inventory::class);
        $productionReport = \Mockery::mock(ProductionReport::class);

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->expects('getInventory')->andReturn($inventory);
        $dossier->expects('getProductionReport')->andReturn($productionReport);
        $dossier->expects('setInventory')->with(null);
        $dossier->expects('setProductionReport')->with(null);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($inventory);
        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($productionReport);

        $this->entityManager->expects('remove')->with($inventory);
        $this->entityManager->expects('remove')->with($productionReport);
        $this->entityManager->expects('flush');
        $this->entityManager->expects('persist')->with($dossier);

        self::assertTrue(
            $this->inventoryService->removeInventories($dossier)
        );
    }
}
