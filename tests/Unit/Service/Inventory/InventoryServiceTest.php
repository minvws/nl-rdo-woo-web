<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Entity\Dossier;
use App\Entity\Inventory;
use App\Entity\InventoryProcessRun;
use App\Entity\RawInventory;
use App\Exception\ProcessInventoryException;
use App\Service\Inventory\InventoryService;
use App\Service\Inventory\Reader\InventoryReaderFactory;
use App\Service\Inventory\Reader\InventoryReaderInterface;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class InventoryServiceTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private DocumentStorageService&MockInterface $documentStorage;
    private InventoryReaderFactory&MockInterface $readerFactory;
    private InventoryService $inventoryService;
    private InventoryProcessRun&MockInterface $run;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->documentStorage = \Mockery::mock(DocumentStorageService::class);
        $this->readerFactory = \Mockery::mock(InventoryReaderFactory::class);
        $this->run = \Mockery::mock(InventoryProcessRun::class);

        $this->inventoryService = new InventoryService(
            $this->entityManager,
            $this->documentStorage,
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

        $this->documentStorage->expects('downloadDocument')->with($this->run)->andReturnFalse();

        $this->expectExceptionObject(ProcessInventoryException::forInventoryCannotBeLoadedFromStorage());

        $this->inventoryService->getReader($this->run);
    }

    public function testGetReaderReturnsAnOpenReaderAndSetsTmpFilenameOnRun(): void
    {
        $this->run->shouldReceive('getFileInfo->isUploaded')->andReturnTrue();
        $this->run->shouldReceive('getFileInfo->getMimetype')->andReturn('text/csv');

        $filename = 'tst/123.csv';
        $this->documentStorage->expects('downloadDocument')->with($this->run)->andReturn($filename);

        $this->run->expects('setTmpFilename')->with($filename);

        $reader = \Mockery::mock(InventoryReaderInterface::class);
        $reader->expects('open')->with($filename);

        $this->readerFactory->expects('create')->andReturn($reader);

        $result = $this->inventoryService->getReader($this->run);

        $this->assertSame($reader, $result);
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

        $this->documentStorage->expects('removeDownload')->with($filename);

        $this->inventoryService->cleanupTmpFile($this->run);
    }

    public function testRemoveInventoriesDoesNothingAndReturnsFalseWhenThereAreNoInventories(): void
    {
        $dossier = \Mockery::mock(Dossier::class);
        $dossier->expects('getInventory')->andReturnNull();
        $dossier->expects('getRawInventory')->andReturnNull();

        $this->assertFalse(
            $this->inventoryService->removeInventories($dossier)
        );
    }

    public function testRemoveInventoriesRemovesAllInventories(): void
    {
        $inventory = \Mockery::mock(Inventory::class);
        $rawInventory = \Mockery::mock(RawInventory::class);

        $dossier = \Mockery::mock(Dossier::class);
        $dossier->expects('getInventory')->andReturn($inventory);
        $dossier->expects('getRawInventory')->andReturn($rawInventory);
        $dossier->expects('setInventory')->with(null);
        $dossier->expects('setRawInventory')->with(null);

        $this->documentStorage->expects('removeFileForEntity')->with($inventory);
        $this->documentStorage->expects('removeFileForEntity')->with($rawInventory);

        $this->entityManager->expects('remove')->with($inventory);
        $this->entityManager->expects('remove')->with($rawInventory);
        $this->entityManager->expects('persist')->with($dossier);

        $this->assertTrue(
            $this->inventoryService->removeInventories($dossier)
        );
    }
}
