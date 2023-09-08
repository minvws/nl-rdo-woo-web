<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Inquiry;
use App\Entity\Inventory;
use App\Entity\RawInventory;
use App\Service\Inventory\Reader\InventoryReaderFactory;
use App\Service\Inventory\Reader\InventoryReadItem;
use App\Service\Storage\DocumentStorageService;
use App\SourceType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This class will process an inventory and generates document entities from the given data.
 * Note that this class does not handle the content of the documents itself, just the metadata.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InventoryService
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly DocumentStorageService $documentStorage,
        private readonly InventoryReaderFactory $readerFactory,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Store the inventory file on disk, process it and attach found documents to the dossier.
     */
    public function processInventory(
        ?UploadedFile $uploadedFile,
        Dossier $dossier,
    ): ProcessInventoryResult {
        $this->doctrine->beginTransaction();
        try {
            $result = $this->realProcessInventory($uploadedFile, $dossier);
        } catch (\Exception $e) {
            $this->doctrine->rollback();
            throw $e;
        }

        if ($result->isSuccessful()) {
            $this->doctrine->commit();
        } else {
            $this->doctrine->rollback();
        }

        return $result;
    }

    protected function realProcessInventory(
        ?UploadedFile $uploadedFile,
        Dossier $dossier
    ): ProcessInventoryResult {
        $result = new ProcessInventoryResult();

        if (! $uploadedFile instanceof UploadedFile) {
            $result->addGenericError('No inventory file provided');

            return $result;
        }

        $inventory = $this->storeRawInventoryFile($uploadedFile, $dossier);
        if (! $inventory) {
            $this->logger->error('Could not store the inventory spreadsheet.', [
                'dossier' => $dossier->getId() ?? 'unknown',
                'filename' => $uploadedFile->getClientOriginalName(),
            ]);

            $result->addGenericError('Could not store the inventory spreadsheet.');

            return $result;
        }

        // Process the spreadsheet and time it
        $start = microtime(true);

        $tmpFilename = $this->documentStorage->downloadDocument($inventory);
        if (! $tmpFilename) {
            $result->addGenericError('Could not download the inventory from document storage.');

            return $result;
        }

        // Create reader for processing the inventory into documents
        $reader = $this->createReader($tmpFilename, $dossier, $inventory->getFileInfo()->getName(), $result);
        if (is_null($reader)) {
            $this->documentStorage->removeDownload($tmpFilename);

            return $result;
        }
        $this->processDocuments($dossier, $inventory, $result, $reader);

        // Create reader for processing the inventory into a sanitized CSV
        $reader = $this->createReader($tmpFilename, $dossier, $inventory->getFileInfo()->getName(), $result);
        if (is_null($reader)) {
            $this->documentStorage->removeDownload($tmpFilename);

            return $result;
        }
        $this->generateSanitizedInventoryCsv($dossier, $reader, $result);

        $this->logger->info('Processed inventory spreadsheet.', [
            'dossier' => $dossier->getId() ?? 'unknown',
            'filename' => $uploadedFile->getClientOriginalName(),
            'duration' => microtime(true) - $start,
            'success' => true,
            'errors' => $result->getRowErrors(),
        ]);

        $this->documentStorage->removeDownload($tmpFilename);

        return $result;
    }

    /**
     * @param iterable<InventoryReadItem> $inventoryReader
     */
    private function processDocuments(
        Dossier $dossier,
        RawInventory $inventory,
        ProcessInventoryResult $result,
        iterable $inventoryReader
    ): void {
        // Store current documents, so we can see which are new and which can be removed
        $tobeRemovedDocs = [];
        foreach ($dossier->getDocuments() as $entry) {
            $tobeRemovedDocs[$entry->getDocumentNr()] = $entry;
        }

        foreach ($inventoryReader as $inventoryItem) {
            $rowIndex = $inventoryItem->getIndex();

            $exception = $inventoryItem->getException();
            if ($exception instanceof \Exception) {
                $this->logger->error('Error while processing row ' . $rowIndex . ' in the spreadsheet.', [
                    'dossier' => $dossier->getId() ?? 'unknown',
                    'filename' => $inventory->getFileInfo()->getName(),
                    'row' => $rowIndex,
                    'exception' => $inventoryItem->getException(),
                ]);

                // Exception occurred, but we still continue with the next row. Just log the error
                $result->addRowError($rowIndex, 'Error reading row: ' . $exception->getMessage());
                continue;
            }

            $documentMetadata = $inventoryItem->getDocumentMetadata();
            if ($documentMetadata instanceof DocumentMetadata) {
                // Create document or attach an existing document to the dossier
                try {
                    $document = $this->mapToDocument($documentMetadata, $dossier);
                    // This document is added (again), so remove it from the tobeRemovedDocs array
                    if (isset($tobeRemovedDocs[$document->getDocumentNr()])) {
                        unset($tobeRemovedDocs[$document->getDocumentNr()]);
                    }
                } catch (\Exception $e) {
                    $this->logger->error("Error while processing row $rowIndex in the spreadsheet.", [
                        'dossier' => $dossier->getId() ?? 'unknown',
                        'filename' => $inventory->getFileInfo()->getName(),
                        'row' => $rowIndex,
                        'exception' => $e->getMessage(),
                    ]);

                    // Exception occurred, but we still continue with the next row. Just log the error
                    $result->addRowError($rowIndex, 'Error while processing row: ' . $e->getMessage());
                }
            }
        }

        // We now have a list of old documents that are linked to the dossier, but are not in the new inventory
        // Remove these documents from the dossier
        foreach ($tobeRemovedDocs as $document) {
            $dossier->removeDocument($document);
        }
    }

    /**
     * Store the inventory to disk and add the inventory document to the dossier.
     */
    protected function storeRawInventoryFile(UploadedFile $upload, Dossier $dossier): ?RawInventory
    {
        $inventory = new RawInventory();
        $inventory->setDossier($dossier);

        $file = $inventory->getFileInfo();
        $file->setSourceType(SourceType::SOURCE_SPREADSHEET);
        $file->setType('pdf');

        // Set original filename
        $filename = 'raw-inventory-' . $dossier->getDossierNr() . '.' . $upload->getClientOriginalExtension();
        $file->setName($filename);

        $this->doctrine->persist($inventory);
        $this->doctrine->flush();

        if (! $this->documentStorage->storeDocument($upload, $inventory)) {
            return null;
        }

        return $inventory;
    }

    /**
     * If the document has case numbers, add it to those inquiries. If those inquiries do not exist
     * yet, create them as well.
     *
     * @param string[] $caseNrs
     */
    protected function addDocumentToCases(array $caseNrs, Document $document): void
    {
        if (empty($caseNrs)) {
            return;
        }

        foreach ($caseNrs as $caseNr) {
            $inquiry = $this->doctrine->getRepository(Inquiry::class)->findOneBy(['casenr' => $caseNr]);
            if (! $inquiry) {
                // Create inquiry if not exists
                $inquiry = new Inquiry();
                $inquiry->setCasenr($caseNr);
                $inquiry->setToken(Uuid::v6()->toBase58());
                $inquiry->setCreatedAt(new \DateTimeImmutable());
            }

            $inquiry->setUpdatedAt(new \DateTimeImmutable());

            // Add this document, and the dossiers it belongs to, to the inquiry
            $inquiry->addDocument($document);
            foreach ($document->getDossiers() as $dossier) {
                $inquiry->addDossier($dossier);
            }

            $this->doctrine->persist($inquiry);
            $this->doctrine->flush();
        }
    }

    /**
     * Process DocumentMetadata. Creates a document if one didn't exist already, or adds an already
     * existing document to the dossier. Also generates or updates inquiries/cases.
     *
     * NOTE: this method does not flush the changes to the database.
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function mapToDocument(DocumentMetadata $documentMetadata, Dossier $dossier): Document
    {
        if (! empty($documentMetadata->getMatter())) {
            $documentNr = $dossier->getDocumentPrefix() . '-' . $documentMetadata->getMatter() . '-' . $documentMetadata->getId();
        } else {
            $documentNr = $dossier->getDocumentPrefix() . '-' . $documentMetadata->getId();
        }

        // Check if this document already exists in a dossier
        $document = $this->doctrine->getRepository(Document::class)->findOneBy([
            'documentNr' => $documentNr,
        ]);

        if ($document && $document->getDossiers()->contains($dossier) === false) {
            // Document exists, but it is not part of the current dossier. We cannot add it
            throw new \RuntimeException(sprintf('Document %s already exists in another dossier', $document->getDocumentId()));
        }

        // If document didn't exist yet, create it
        if (! $document) {
            $document = new Document();
        }

        // Update all fields from the existing (or new) document.
        $documentMetadata->mapToDocument($document, $documentNr);

        $this->doctrine->persist($document);
        $this->doctrine->flush();

        // Add document to the dossier
        $dossier->addDocument($document);

        // We need to persist the dossier, because we added a document to it. This would also make the dossier
        // visible in the document, so the addDocumentToCases() method can find the dossier.
        $this->doctrine->persist($dossier);
        $this->doctrine->flush();

        // Add document to woo request case nr (if any), or create new woo request if not already present
        $this->addDocumentToCases($documentMetadata->getCaseNumbers(), $document);

        return $document;
    }

    /**
     * @param iterable<InventoryReadItem> $inventoryReader
     */
    protected function generateSanitizedInventoryCsv(Dossier $dossier, iterable $inventoryReader, ProcessInventoryResult $result): void
    {
        // Generate CSV with sanitized fields
        $csvFilename = tempnam(sys_get_temp_dir(), 'inventory');
        if (! $csvFilename) {
            $this->logger->error('Could not create temporary file for sanitized inventory CSV.');

            return;
        }

        $fp = fopen($csvFilename, 'w');
        if (! $fp) {
            $this->logger->error('Could not open temporary file for sanitized inventory CSV.');

            return;
        }

        fputcsv($fp, [
            'Document ID',
            'Document naam',
            'Beoordeling',
            'Beoordelingsgrond',
            'Toelichting',
            'Publieke link',
            'Opgeschort',
            'Definitief ID',
        ]);

        foreach ($inventoryReader as $inventoryItem) {
            $meta = $inventoryItem->getDocumentMetadata();
            if (! $meta) {
                continue;
            }

            fputcsv($fp, [
                $meta->getId(),
                str_replace(';', ' ', $meta->getFilename()),
                $this->translator->trans($meta->getJudgement()->value),
                join(' ', $meta->getGrounds()),
                $meta->getRemark(),
                $meta->getLink(),
                $meta->isSuspended() ? 'yes' : '',
                '',
            ]);
        }

        fclose($fp);

        // Create or update inventory document and add to dossier
        $inventory = $dossier->getInventory();
        if ($inventory == null) {
            $inventory = new Inventory();
            $inventory->setDossier($dossier);
        }

        $file = $inventory->getFileInfo();
        $file->setSourceType(SourceType::SOURCE_SPREADSHEET);
        $file->setType('csv');
        $filename = 'inventory-' . $dossier->getDossierNr() . '.csv';
        $file->setName($filename);

        $this->doctrine->persist($inventory);

        $dossier->setInventory($inventory);
        $this->doctrine->persist($dossier);
        $this->doctrine->flush();

        // Store inventory file
        if (! $this->documentStorage->storeDocument(new \SplFileInfo($csvFilename), $inventory)) {
            $result->addGenericError('Could not store the sanitized inventory spreadsheet.');
        }
    }

    /**
     * @return iterable<InventoryReadItem>|null
     */
    protected function createReader(string $filePath, Dossier $dossier, ?string $filename, ProcessInventoryResult $result): ?iterable
    {
        if (! $filename) {
            return null;
        }

        try {
            $inventoryReader = $this->readerFactory->create();
            $inventoryReader->open($filePath);

            return $inventoryReader->getDocumentMetadataGenerator($dossier);
        } catch (\Exception $exception) {
            $result->addGenericError('Error while trying to read the spreadsheet: ' . $exception->getMessage());

            $this->logger->error('Error while trying to read the spreadsheet.', [
                'dossier' => $dossier->getId() ?? 'unknown',
                'filename' => $filename,
                'exception' => $exception->getMessage(),
            ]);
        }

        return null;
    }
}
