<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\SourceType;
use Shared\Exception\ProcessInventoryException;
use Shared\Service\Inventory\Reader\InventoryReaderFactory;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;
use Shared\Service\Storage\EntityStorageService;

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

    public function getReader(ProductionReportProcessRun $run): InventoryReaderInterface
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

    public function cleanupTmpFile(ProductionReportProcessRun $run): void
    {
        if ($run->getTmpFilename()) {
            $this->entityStorageService->removeDownload($run->getTmpFilename());
            $run->setTmpFilename(null);
        }
    }

    public function removeInventories(WooDecision $dossier): bool
    {
        $inventory = $dossier->getInventory();
        $productionReport = $dossier->getProductionReport();

        if (! $inventory && ! $productionReport) {
            return false;
        }

        if ($inventory) {
            $this->entityStorageService->deleteAllFilesForEntity($inventory);
            $this->doctrine->remove($inventory);
            $dossier->setInventory(null);
        }

        if ($productionReport) {
            $this->entityStorageService->deleteAllFilesForEntity($productionReport);
            $this->doctrine->remove($productionReport);
            $dossier->setProductionReport(null);
        }

        $this->doctrine->flush();
        $this->doctrine->persist($dossier);

        return true;
    }

    public function storeProductionReport(ProductionReportProcessRun $run): void
    {
        $inventory = new ProductionReport();
        $inventory->setDossier($run->getDossier());

        $file = $inventory->getFileInfo();
        $file->setSourceType(SourceType::SPREADSHEET);
        $file->setType('xlsx');

        $defaultFilename = 'production-report-' . $run->getDossier()->getDossierNr() . '.xlsx';
        $file->setName($run->getFileInfo()->getName() ?? $defaultFilename);

        $this->doctrine->persist($inventory);

        $tmpFilename = $run->getTmpFilename();
        if (! $tmpFilename) {
            throw ProcessInventoryException::forInventoryCannotBeLoadedFromStorage();
        }

        $fileInfo = new \SplFileInfo($tmpFilename);
        if (! $this->entityStorageService->storeEntity($fileInfo, $inventory, false)) {
            throw new \RuntimeException('Cannot store production report');
        }
    }
}
