<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\ProductionReportProcessRun;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentRepository;
use App\Exception\ProcessInventoryException;
use App\Exception\TranslatableException;
use App\Service\Inventory\Progress\RunProgress;
use App\Service\Inventory\Reader\InventoryReaderInterface;

class InventoryComparator
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentComparator $documentComparator,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function determineChangeset(
        ProductionReportProcessRun $run,
        InventoryReaderInterface $reader,
        RunProgress $runProgress,
    ): InventoryChangeset {
        $dossier = $run->getDossier();
        $documentGenerator = $reader->getDocumentMetadataGenerator($dossier);
        $tobeRemovedDocs = $this->getDocumentNrList($dossier);

        $changeset = new InventoryChangeset();
        foreach ($documentGenerator as $inventoryItem) {
            $rowIndex = $inventoryItem->getIndex();

            $runProgress->update($rowIndex);

            $exception = $inventoryItem->getException();
            if ($exception instanceof \Exception) {
                $this->handleRowError($rowIndex, $run, $exception);

                continue;
            }

            $documentMetadata = $inventoryItem->getDocumentMetadata();
            if (! $documentMetadata instanceof DocumentMetadata) {
                continue;
            }

            try {
                $documentNr = DocumentNumber::fromDossierAndDocumentMetadata($dossier, $documentMetadata);
                $document = $this->documentRepository->findByDocumentNumber($documentNr);

                if ($document === null) {
                    $changeset->markAsAdded($documentNr);

                    continue;
                }

                if ($document->getDossiers()->contains($dossier) === false) {
                    $run->addRowException($rowIndex, ProcessInventoryException::forDocumentExistsInAnotherDossier($document));
                }

                // This document is still in the inventory, so remove it from the tobeRemovedDocs array
                unset($tobeRemovedDocs[$document->getDocumentNr()]);

                if ($this->documentComparator->needsUpdate($dossier, $document, $documentMetadata)) {
                    $changeset->markAsUpdated($documentNr);
                } else {
                    $changeset->markAsUnchanged($documentNr);
                }
            } catch (TranslatableException $exception) {
                $run->addRowException($rowIndex, $exception);
            }

            unset($document);
        }

        // The remaining docs in $tobeRemovedDocs are not in the new inventory, so should be removed.
        $this->addDeletesToChangeset($tobeRemovedDocs, $dossier, $run, $changeset);

        return $changeset;
    }

    private function handleRowError(
        int $rowIndex,
        ProductionReportProcessRun $run,
        \Exception $exception,
    ): void {
        // Exception occurred, but we still continue with the next row. Just log the error
        if (! $exception instanceof TranslatableException) {
            $exception = ProcessInventoryException::forGenericRowException($exception);
        }

        $run->addRowException($rowIndex, $exception);
    }

    /**
     * @return array<string, int>
     */
    private function getDocumentNrList(WooDecision $dossier): array
    {
        // Important: don't use $dossier->getDocuments which loads all document entities into memory and the entitymanager
        $documentNrs = $this->documentRepository->getAllDocumentNumbersForDossier($dossier);

        // Use values as keys for faster lookups
        return array_fill_keys($documentNrs, 1);
    }

    /**
     * @param array<string, int> $tobeRemovedDocs
     */
    public function addDeletesToChangeset(
        array $tobeRemovedDocs,
        WooDecision $dossier,
        ProductionReportProcessRun $run,
        InventoryChangeset $changeset,
    ): void {
        foreach (array_keys($tobeRemovedDocs) as $documentNr) {
            if (! $dossier->getStatus()->isConcept()) {
                $run->addGenericException(ProcessInventoryException::forMissingDocument($documentNr));
            }

            $changeset->markAsDeleted($documentNr);
        }
    }
}
