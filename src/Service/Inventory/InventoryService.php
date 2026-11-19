<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Upload\FileType\FileExtension;
use Shared\Exception\Import\InventoryCannotBeLoadedFromStorageException;
use Shared\Service\Inventory\Reader\InventoryReaderFactory;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;
use Shared\Service\Storage\EntityStorageService;

use function sprintf;

/**
 * This class will process an inventory and generates document entities from the given data.
 * Note that this class does not handle the content of the documents itself, just the metadata.
 */
readonly class InventoryService
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private EntityStorageService $entityStorageService,
        private InventoryReaderFactory $readerFactory,
    ) {
    }

    public function getReader(EntityWithFileInfo $run): InventoryReaderInterface
    {
        if (! $run->getFileInfo()->isUploaded()) {
            throw new RuntimeException('Input file missing, cannot process inventory');
        }

        $stream = $this->entityStorageService->retrieveResourceEntity($run);
        if ($stream === null) {
            throw new InventoryCannotBeLoadedFromStorageException();
        }

        $inventoryReader = $this->readerFactory->create($run->getFileInfo()->getMimetype() ?? '');
        $inventoryReader->open($stream);

        return $inventoryReader;
    }

    public function removeInventories(WooDecision $dossier): bool
    {
        $inventory = $dossier->getInventory();
        $productionReport = $dossier->getProductionReport();

        if (! $inventory && ! $productionReport) {
            return false;
        }

        if ($inventory instanceof Inventory) {
            $this->entityStorageService->deleteAllFilesForEntity($inventory);
            $this->doctrine->remove($inventory);
            $dossier->setInventory(null);
        }

        if ($productionReport instanceof ProductionReport) {
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
        $file->setType(FileExtension::XLSX->value);

        $defaultFilename = sprintf('production-report-%s.%s', $run->getDossier()->getDossierNumber(), FileExtension::XLSX->value);
        $file->setName($run->getFileInfo()->getName() ?? $defaultFilename);

        $this->doctrine->persist($inventory);

        if (! $this->entityStorageService->copyEntity($run, $inventory)) {
            throw new RuntimeException('Cannot store production report');
        }
    }
}
