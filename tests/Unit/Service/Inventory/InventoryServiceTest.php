<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Exception\Import\InventoryCannotBeLoadedFromStorageException;
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
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->entityStorageService = Mockery::mock(EntityStorageService::class);
        $this->readerFactory = Mockery::mock(InventoryReaderFactory::class);
        $this->run = Mockery::mock(ProductionReportProcessRun::class);

        $this->inventoryService = new InventoryService(
            $this->entityManager,
            $this->entityStorageService,
            $this->readerFactory,
        );

        parent::setUp();
    }

    public function testGetReaderThrowsExceptionWhenUploadIsMissing(): void
    {
        $this->run->expects('getFileInfo->isUploaded')->andReturnFalse();

        $this->expectException(RuntimeException::class);

        $this->inventoryService->getReader($this->run);
    }

    public function testGetReaderThrowsExceptionWhenFileCannotBeRead(): void
    {
        $this->run->expects('getFileInfo->isUploaded')->andReturnTrue();

        $this->entityStorageService->expects('retrieveResourceEntity')->with($this->run)->andReturnNull();

        $this->expectExceptionObject(new InventoryCannotBeLoadedFromStorageException());

        $this->inventoryService->getReader($this->run);
    }

    public function testGetReaderReturnsAnOpenReader(): void
    {
        $this->run->expects('getFileInfo->isUploaded')->andReturnTrue();
        $this->run->expects('getFileInfo->getMimetype')->andReturn($this->getFaker()->mimeType());

        $stream = Utils::tryFopen('php://memory', 'r');
        $this->entityStorageService->expects('retrieveResourceEntity')->with($this->run)->andReturn($stream);

        $reader = Mockery::mock(InventoryReaderInterface::class);
        $reader->expects('open')->with($stream);

        $this->readerFactory->expects('create')->andReturn($reader);

        $result = $this->inventoryService->getReader($this->run);

        self::assertSame($reader, $result);
    }

    public function testStoreProductionReportCopiesTheRunFile(): void
    {
        $name = $this->getFaker()->word();

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('setProductionReport')->with(Mockery::type(ProductionReport::class));
        $dossier->expects('getDossierNumber')->andReturn($this->getFaker()->word());

        $run = Mockery::mock(ProductionReportProcessRun::class);
        $run->expects('getDossier')->times(2)->andReturn($dossier);
        $run->expects('getFileInfo->getName')->andReturn($name);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('persist')->with(Mockery::type(ProductionReport::class));

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService
            ->expects('copyEntity')
            ->with($run, Mockery::on(static function (ProductionReport $productionReport) use ($name): bool {
                return $productionReport->getFileInfo()->getName() === $name;
            }))
            ->andReturnTrue();

        $inventoryService = new InventoryService(
            $entityManager,
            $entityStorageService,
            Mockery::mock(InventoryReaderFactory::class),
        );

        $inventoryService->storeProductionReport($run);
    }

    public function testStoreProductionReportThrowsExceptionWhenCopyFails(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('setProductionReport')->with(Mockery::type(ProductionReport::class));
        $dossier->expects('getDossierNumber')->andReturn($this->getFaker()->word());

        $run = Mockery::mock(ProductionReportProcessRun::class);
        $run->expects('getDossier')->times(2)->andReturn($dossier);
        $run->expects('getFileInfo->getName')->andReturn($this->getFaker()->word());

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('persist')->with(Mockery::type(ProductionReport::class));

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService->expects('copyEntity')->with($run, Mockery::type(ProductionReport::class))->andReturnFalse();

        $inventoryService = new InventoryService(
            $entityManager,
            $entityStorageService,
            Mockery::mock(InventoryReaderFactory::class),
        );

        $this->expectException(RuntimeException::class);

        $inventoryService->storeProductionReport($run);
    }

    public function testRemoveInventoriesDoesNothingAndReturnsFalseWhenThereAreNoInventories(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getInventory')->andReturnNull();
        $dossier->expects('getProductionReport')->andReturnNull();

        self::assertFalse(
            $this->inventoryService->removeInventories($dossier),
        );
    }

    public function testRemoveInventoriesRemovesAllInventories(): void
    {
        $inventory = Mockery::mock(Inventory::class);
        $productionReport = Mockery::mock(ProductionReport::class);

        $dossier = Mockery::mock(WooDecision::class);
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
            $this->inventoryService->removeInventories($dossier),
        );
    }
}
