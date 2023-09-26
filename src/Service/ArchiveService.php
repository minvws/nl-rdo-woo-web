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
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly DocumentStorageService $storageService,
        private readonly FilesystemOperator $storage,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Generates a ZIP archive for the given batch download. Returns true on success (or already created), false otherwise.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                if (! $document->shouldBeUploaded()) {
                    continue;
                }

                $documents[] = $document->getDocumentNr();
            }
        }

        $batchQuery = $this->doctrine->getConnection()->createQueryBuilder();
        $batchQuery
            ->select('COUNT(*)')
            ->from('batch_download')
            ->where('id = :id')
            ->setParameter('id', $batch->getId()->toRfc4122());

        // Add all document files
        $empty = true;
        $localPaths = [];
        foreach ($documents as $documentNr) {
            // Check if the batch still exists. If not, we can stop processing because we are superseded by a new batch.
            $count = (int) $batchQuery->executeQuery()->fetchNumeric();
            if ($count === 0) {
                $this->logger->info('Batch download has been deleted, stopping processing', [
                    'batch_id' => $batch->getId()->toRfc4122(),
                ]);

                return false;
            }

            // Check (again) if the document exists in the dossier.
            $document = $this->findInDossier($batch->getDossier(), $documentNr);
            if (! $document || ! $document->isUploaded() || ! $document->shouldBeUploaded()) {
                continue;
            }

            // Load the document from the storage service locally, and add it to the ZIP archive.
            $localPath = $this->storageService->downloadDocument($document);
            if (! $localPath) {
                $this->logger->error('Could not save document to local path', [
                    'batch_id' => $batch->getId()->toRfc4122(),
                    'document_id' => $document->getId()->toRfc4122(),
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
            $empty = false;

            $localPaths[] = $localPath;
        }

        // Finished processing
        $zip->close();

        // Remove all local files
        foreach ($localPaths as $localPath) {
            $this->storageService->removeDownload($localPath);
        }

        if (! $empty && $this->saveZip($zipArchivePath, $batch->getFilename(), $batch)) {
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
            if ($document->getDocumentNr() === $documentNr && $document->shouldBeUploaded()) {
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
                'batch' => $batch->getId()->toRfc4122(),
                'path' => $zipArchivePath,
            ]);

            return false;
        }

        try {
            $this->storage->writeStream($destinationPath, $stream);
        } catch (FilesystemException $e) {
            $this->logger->error('Failed to move ZIP archive to storage', [
                'batch' => $batch->getId()->toRfc4122(),
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
                'batch' => $batch->getId()->toRfc4122(),
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
            /** @var Document $document */
            foreach ($dossier->getUploadStatus()->getExpectedDocuments() as $document) {
                $documents[] = $document->getDocumentNr();
            }
        }

        // Prune all expired documents (garbage collection in case cron doesn't work)
        $this->doctrine->getRepository(BatchDownload::class)->pruneExpired();

        $batches = $this->doctrine->getRepository(BatchDownload::class)->findBy([
            'dossier' => $dossier,
        ]);

        foreach ($batches as $batch) {
            // Both completed and pending are ok. Otherwise we get a stampede when a zip is being created and the user refreshes the page.
            if (
                $batch->getStatus() !== BatchDownload::STATUS_COMPLETED
                && $batch->getStatus() !== BatchDownload::STATUS_PENDING
            ) {
                continue;
            }

            if ($batch->getDocuments() === $documents) {
                return $batch;
            }
        }

        return null;
    }

    /**
     * Deletes all batches that contain the given dossier.
     */
    public function deleteDossierArchives(Dossier $dossier): void
    {
        $batches = $this->doctrine->getRepository(BatchDownload::class)->findBy([
            'dossier' => $dossier->getId(),
        ]);

        foreach ($batches as $batch) {
            $this->doctrine->remove($batch);
        }

        $this->doctrine->flush();
    }

    /**
     * Deletes all batches that contain the given document.
     */
    public function deleteByDocument(Document $document): void
    {
        $batches = $this->doctrine->getRepository(BatchDownload::class)->findBy([
            'documents' => $document->getDocumentNr(),
        ]);

        foreach ($batches as $batch) {
            $this->doctrine->remove($batch);
        }

        $this->doctrine->flush();
    }

    public function createArchiveForCompleteDossier(Dossier $dossier): void
    {
        $batch = new BatchDownload();
        $batch->setStatus(BatchDownload::STATUS_PENDING);
        $batch->setDossier($dossier);
        $batch->setDownloaded(0);
        $batch->setExpiration(new \DateTimeImmutable('+10 years'));
        $batch->setDocuments([]);

        $this->doctrine->persist($batch);
        $this->doctrine->flush();

        $this->generateArchive($batch);
    }
}
