<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\BatchDownload;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

/**
 * This class is responsible for generated ZIP archives for batch downloads based on the BatchDownload entity given. Once the ZIP has been
 * created, the status of the batch download will be set to "completed".
 */
class ArchiveService
{
    protected EntityManagerInterface $doctrine;
    protected DocumentStorageService $storageService;
    protected FilesystemOperator $storage;
    protected LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        DocumentStorageService $storageService,
        FilesystemOperator $storage,
        LoggerInterface $logger
    ) {
        $this->doctrine = $entityManager;
        $this->storageService = $storageService;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    /**
     * Generates a ZIP archive for the given batch download. Returns true on success (or already created), false otherwise.
     */
    public function generateArchive(BatchDownload $batch): bool
    {
        // Already processed
        if ($batch->getStatus() === BatchDownload::STATUS_COMPLETED) {
            return true;
        }

        // Create ZIP archive
        $zipArchivePath = sprintf('%s/archive-%s.zip', sys_get_temp_dir(), $batch->getId()->toBase58());
        $zip = new \ZipArchive();
        $zip->open($zipArchivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Add all document files
        foreach ($batch->getDocuments() as $documentNr) {
            // Check (again) if the document exists in the dossier.
            $document = $this->findInDossier($batch->getDossier(), $documentNr);
            if (! $document) {
                continue;
            }

            // Load the document from the storage service locally, and add it to the ZIP archive.
            $localPath = $this->storageService->downloadDocument($document);
            if (! $localPath) {
                $this->logger->error('Could not save document to local path', [
                    'batch_id' => $batch->getId()->toBase58(),
                    'document_id' => $document->getId()->toBase58(),
                ]);

                continue;
            }
            $zip->addFile($localPath, $document->getDocumentNr() . '-' . $document->getFilename());
            $this->storageService->removeDownload($localPath);
        }

        // Finished processing
        $zip->close();

        $destinationPath = sprintf('batch-%s.zip', $batch->getId()->toBase58());
        if ($this->saveZip($zipArchivePath, $destinationPath, $batch)) {
            $batch->setStatus(BatchDownload::STATUS_COMPLETED);
        } else {
            $batch->setStatus(BatchDownload::STATUS_FAILED);
        }

        // Save new status and size
        $fileSize = filesize($zipArchivePath);
        $batch->setSize(is_int($fileSize) ? strval($fileSize) : '0');   // size == bigint == string
        $this->doctrine->persist($batch);
        $this->doctrine->flush();

        unlink($zipArchivePath); // Remove the temporary ZIP archive (we have it in the storage now)

        return true;
    }

    /**
     * Returns the document with the given document number from the dossier, or null when not found.
     */
    protected function findInDossier(Dossier $dossier, string $documentNr): ?Document
    {
        foreach ($dossier->getDocuments() as $document) {
            if ($document->getDocumentNr() === $documentNr) {
                return $document;
            }
        }

        return null;
    }

    protected function saveZip(string $zipArchivePath, string $destinationPath, BatchDownload $batch): bool
    {
        // Open the file as a SplFileObject, so we can stream it to the storage adapter
        $stream = fopen($zipArchivePath, 'r');
        if (! is_resource($stream)) {
            $this->logger->error('Could not open zip file stream', [
                'batch' => $batch->getId()->toBase58(),
                'path' => $zipArchivePath,
            ]);

            return false;
        }

        try {
            $this->storage->writeStream($destinationPath, $stream);
        } catch (FilesystemException $e) {
            $this->logger->error('Failed to move ZIP archive to storage', [
                'batch' => $batch->getId()->toBase58(),
                'source_path' => $zipArchivePath,
                'destination_path' => $destinationPath,
                'exception' => $e->getMessage(),
            ]);

            fclose($stream);

            return false;
        }

        fclose($stream);

        return true;
    }
}
