<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\Dossier;
use App\Entity\InventoryProcessRun;
use App\Entity\RawInventory;
use App\Exception\ProcessInventoryException;
use App\Service\Inventory\Reader\InventoryReaderFactory;
use App\Service\Inventory\Reader\InventoryReaderInterface;
use App\Service\Storage\EntityStorageService;
use App\SourceType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * This class will process an inventory and generates document entities from the given data.
 * Note that this class does not handle the content of the documents itself, just the metadata.
 */
class InventoryService
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly EntityStorageService $entityStorageService,
        private readonly InventoryReaderFactory $readerFactory,
    ) {
    }

    public function getReader(InventoryProcessRun $run): InventoryReaderInterface
    {
        if (! $run->getFileInfo()->isUploaded()) {
            throw new \RuntimeException('Input file missing, cannot process inventory');
        }

        $tmpFilename = $this->entityStorageService->downloadEntity($run);
        if (! $tmpFilename) {
            throw ProcessInventoryException::forInventoryCannotBeLoadedFromStorage();
        }

        $run->setTmpFilename($tmpFilename);

        $inventoryReader = $this->readerFactory->create($run->getFileInfo()->getMimetype() ?? '');
        $inventoryReader->open($tmpFilename);

        return $inventoryReader;
    }

    public function cleanupTmpFile(InventoryProcessRun $run): void
    {
        if ($run->getTmpFilename()) {
            $this->entityStorageService->removeDownload($run->getTmpFilename());
            $run->setTmpFilename(null);
        }
    }

    public function removeInventories(Dossier $dossier): bool
    {
        $inventory = $dossier->getInventory();
        $rawInventory = $dossier->getRawInventory();

        if (! $inventory && ! $rawInventory) {
            return false;
        }

        if ($inventory) {
            $this->entityStorageService->removeFileForEntity($inventory);
            $this->doctrine->remove($inventory);
            $dossier->setInventory(null);
        }

        if ($rawInventory) {
            $this->entityStorageService->removeFileForEntity($rawInventory);
            $this->doctrine->remove($rawInventory);
            $dossier->setRawInventory(null);
        }

        $this->doctrine->persist($dossier);

        return true;
    }

    /**
     * Store the inventory to disk and add the inventory document to the dossier.
     */
    public function storeRawInventory(InventoryProcessRun $run): void
    {
        $inventory = new RawInventory();
        $inventory->setDossier($run->getDossier());

        $file = $inventory->getFileInfo();
        $file->setSourceType(SourceType::SPREADSHEET);
        $file->setType('xlsx');

        $defaultFilename = 'raw-inventory-' . $run->getDossier()->getDossierNr() . '.xlsx';
        $file->setName($run->getFileInfo()->getName() ?? $defaultFilename);

        $this->doctrine->persist($inventory);

        $tmpFilename = $run->getTmpFilename();
        if (! $tmpFilename) {
            throw ProcessInventoryException::forInventoryCannotBeLoadedFromStorage();
        }

        $fileInfo = new \SplFileInfo($tmpFilename);
        if (! $this->entityStorageService->storeEntity($fileInfo, $inventory, false)) {
            throw new \RuntimeException('Cannot store raw inventory');
        }
    }
}
