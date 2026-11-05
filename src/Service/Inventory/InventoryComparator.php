<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use Exception;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Exception\Import\DocumentExistsInAnotherDossierException;
use Shared\Exception\Import\ImportException;
use Shared\Exception\Import\MissingDocumentException;
use Shared\Exception\Import\UnexpectedRowException;
use Shared\Service\Import\ImportError;
use Shared\Service\Inventory\Progress\RunProgress;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;
use Shared\ValueObject\DocumentNumber;

use function array_fill_keys;
use function array_keys;
use function array_map;

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
        $tobeRemovedDocs = $this->getDocumentNumberList($dossier);

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
                $documentNumber = DocumentNumber::fromPublicationContextAndDocumentId(
                    $documentMetadata->getPublicationContext(),
                    $documentMetadata->getId(),
                );
                $document = $this->documentRepository->findByDocumentNumber($documentNumber);

                if ($document === null) {
                    $changeset->markAsAdded($documentNumber);

                    continue;
                }

                if ($document->getDossiers()->contains($dossier) === false) {
                    $run->addRowError(
                        $rowIndex,
                        ImportError::fromException(
                            new DocumentExistsInAnotherDossierException($document->getDocumentId()->toString()),
                        ),
                    );
                }

                // This document is still in the inventory, so remove it from the tobeRemovedDocs array
                unset($tobeRemovedDocs[$document->getDocumentNumber()->toString()]);

                if ($this->documentComparator->needsUpdate($dossier, $document, $documentMetadata)) {
                    $changeset->markAsUpdated($documentNumber);
                } else {
                    $changeset->markAsUnchanged($documentNumber);
                }
            } catch (ImportException $importException) {
                $run->addRowError($rowIndex, ImportError::fromException($importException));
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
        if (! $exception instanceof ImportException) {
            $exception = new UnexpectedRowException($exception);
        }

        $run->addRowError($rowIndex, ImportError::fromException($exception));
    }

    /**
     * @return array<string, int>
     */
    private function getDocumentNumberList(WooDecision $dossier): array
    {
        // Important: don't use $dossier->getDocuments which loads all document entities into memory and the entitymanager
        $documentNumbers = $this->documentRepository->getAllDocumentNumbersForDossier($dossier);

        // Use values as keys for faster lookups
        return array_fill_keys(
            array_map(
                static fn (DocumentNumber $documentNumber): string => $documentNumber->toString(),
                $documentNumbers,
            ),
            1,
        );
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
        foreach (array_keys($tobeRemovedDocs) as $documentNumber) {
            if (! $dossier->getStatus()->isConcept()) {
                $run->addGenericError(ImportError::fromException(new MissingDocumentException($documentNumber)));
            }

            $changeset->markAsDeleted(DocumentNumber::fromString($documentNumber));
        }
    }
}
