<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\InventoryProcessRun;
use App\Exception\ProcessInventoryException;
use App\Exception\TranslatableException;
use App\Service\DossierService;
use App\Service\Inquiry\InquiryService;
use App\Service\Inventory\Progress\ProgressUpdater;
use App\Service\Inventory\Progress\RunProgress;
use App\Service\Inventory\Reader\InventoryReaderInterface;
use App\Service\Logging\LoggingHelper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * This class will process an inventory and generates document entities from the given data.
 * Note that this class does not handle the content of the documents itself, just the metadata.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InventoryRunProcessor
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly InquiryService $inquiryService,
        private readonly LoggingHelper $loggingHelper,
        private readonly InventoryComparator $inventoryComparator,
        private readonly InventoryUpdater $inventoryUpdater,
        private readonly InventoryService $inventoryService,
        private readonly DossierService $dossierService,
        private readonly ProgressUpdater $progressUpdater,
    ) {
    }

    /**
     * Process an initial inventory file and attach found documents to the dossier.
     *
     * @throws \RuntimeException
     */
    public function process(InventoryProcessRun $run): void
    {
        try {
            $this->loggingHelper->disableAll();
            $this->inquiryService->clearLookupCache();

            $inventoryReader = $this->inventoryService->getReader($run);

            if ($run->isPending()) {
                $this->processComparison($run, $inventoryReader);
            }

            if ($run->isConfirmed()) {
                $this->processUpdates($run, $inventoryReader);
            }
        } catch (\Exception $exception) {
            if (! $exception instanceof TranslatableException) {
                $exception = ProcessInventoryException::forOtherException($exception);
            }

            $run->addGenericException($exception);
            $run->fail();
        } finally {
            if ($run->isFinal()) {
                $this->inventoryService->cleanupTmpFile($run);
            }

            $this->doctrine->persist($run);
            $this->doctrine->flush();
        }
    }

    private function processComparison(InventoryProcessRun $run, InventoryReaderInterface $inventoryReader): void
    {
        $run->startComparing();
        $this->doctrine->persist($run);
        $this->doctrine->flush();

        $runProgress = new RunProgress($this->progressUpdater, $run, $inventoryReader->getCount());

        $changeset = $this->inventoryComparator->determineChangeset($run, $inventoryReader, $runProgress);
        if ($changeset->isEmpty()) {
            $run->addGenericException(ProcessInventoryException::forNoChanges());
        }

        if ($run->hasErrors()) {
            $run->fail();

            return;
        }

        $runProgress->finish();
        $run->setChangeset($changeset);
    }

    private function processUpdates(InventoryProcessRun $run, InventoryReaderInterface $inventoryReader): void
    {
        $run->startUpdating();
        $this->doctrine->persist($run);
        $this->doctrine->flush();

        $dossier = $run->getDossier();
        $changeset = $run->getChangeset();
        if ($changeset === null) {
            throw new \RuntimeException('No changeset available during inventory update');
        }

        // Since we need to do row iterations (compare + update + async messages) multiply the total count for progress
        $runProgress = new RunProgress($this->progressUpdater, $run, $inventoryReader->getCount() * 2);

        // If we get here the changeset is valid, so we can remove the existing inventories and apply the changes
        $this->inventoryService->removeInventories($dossier);
        $this->doctrine->flush();

        $this->inventoryUpdater->applyChangesetToDatabase($dossier, $inventoryReader, $changeset, $runProgress);

        $this->inventoryService->storeRawInventory($run);

        // The validateCompletion call will also flush all changes to the database
        $this->dossierService->validateCompletion($dossier);

        $this->inventoryUpdater->sendMessagesForChangeset($changeset, $dossier, $runProgress);

        $runProgress->finish();
        $run->finish();
    }
}
