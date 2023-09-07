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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
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

        $documents = $batch->getDocuments();
        if (count($documents) === 0) {
            foreach ($batch->getDossier()->getDocuments() as $document) {
                $documents[] = $document->getDocumentNr();
            }
        }

        // Add all document files
        $localPaths = [];
        foreach ($documents as $documentNr) {
            // Check (again) if the document exists in the dossier.
            $document = $this->findInDossier($batch->getDossier(), $documentNr);
            if (! $document || ! $document->isUploaded()) {
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

            // Generate correct filename for this document
            $fileName = $document->getDocumentNr() . '-' . $document->getFileInfo()->getName();
            if (! str_ends_with(strtolower($fileName), '.pdf')) {
                $fileName .= '.pdf';
            }

            $sanitizer = new FilenameSanitizer($fileName);
            $sanitizer->stripAdditionalCharacters();
            $sanitizer->stripIllegalFilesystemCharacters();
            $sanitizer->stripRiskyCharacters();
            $fileName = $sanitizer->getFilename();

            $zip->addFile($localPath, $fileName);

            $localPaths[] = $localPath;
        }

        // Finished processing
        $zip->close();

        // Remove all local files
        foreach ($localPaths as $localPath) {
            $this->storageService->removeDownload($localPath);
        }

        if ($this->saveZip($zipArchivePath, $batch->getFilename(), $batch)) {
            $batch->setStatus(BatchDownload::STATUS_COMPLETED);
        } else {
            $batch->setStatus(BatchDownload::STATUS_FAILED);
        }

        // Store the documents in the batch
        $batch->setDocuments($documents);

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

    /**
     * @return false|resource
     */
    public function getZipStream(BatchDownload $batch)
    {
        try {
            return $this->storage->readStream($batch->getFilename());
        } catch (FilesystemException $e) {
            $this->logger->error('Failed open ZIP archive ', [
                'batch' => $batch->getId()->toBase58(),
                'path' => $batch->getFilename(),
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param string[] $documents
     */
    public function archiveExists(Dossier $dossier, array $documents): ?BatchDownload
    {
        // No documents mean all documents of the dossier
        if (count($documents) === 0) {
            foreach ($dossier->getDocuments() as $document) {
                $documents[] = $document->getDocumentNr();
            }
        }

        // Prune all expired documents (garbage collection in case cron doesn't work)
        $this->doctrine->getRepository(BatchDownload::class)->pruneExpired();

        $batches = $this->doctrine->getRepository(BatchDownload::class)->findBy([
            'status' => BatchDownload::STATUS_COMPLETED,
            'dossier' => $dossier,
        ]);

        foreach ($batches as $batch) {
            if ($batch->getDocuments() === $documents) {
                return $batch;
            }
        }

        return null;
    }
}
