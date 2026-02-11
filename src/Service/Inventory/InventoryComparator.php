<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use Exception;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Exception\ProcessInventoryException;
use Shared\Exception\TranslatableException;
use Shared\Service\Inventory\Progress\RunProgress;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;

use function array_fill_keys;
use function array_keys;

class InventoryComparator
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentComparator $documentComparator,
    ) {
    }

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
            if ($exception instanceof Exception) {
                $this->handleRowError($rowIndex, $run, $exception);

                continue;
            }

            $documentMetadata = $inventoryItem->getDocumentMetadata();
            if (! $documentMetadata instanceof DocumentMetadata) {
                continue;
            }

            try {
                $documentNr = DocumentNumber::fromDossierAndDocumentMetadata($dossier, $documentMetadata);
                $document = $this->documentRepository->findOneByDocumentNrCaseInsensitive($documentNr->getValue());

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
        Exception $exception,
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
