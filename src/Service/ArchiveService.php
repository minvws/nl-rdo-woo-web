<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\BatchDownload;
use App\Entity\Document;
use App\Entity\EntityWithBatchDownload;
use App\Repository\DocumentRepository;
use App\Service\Storage\EntityStorageService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

/**
 * This class is responsible for generated ZIP archives for batch downloads based on the BatchDownload entity given.
 * Once the ZIP has been created, the status of the batch download will be set to "completed".
 */
class ArchiveService
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly EntityStorageService $entityStorageService,
        private readonly FilesystemOperator $storage,
        private readonly LoggerInterface $logger,
        private readonly DocumentRepository $documentRepository,
    ) {
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

        $batchQuery = $this->doctrine->getConnection()->createQueryBuilder();
        $batchQuery
            ->select('COUNT(*)')
            ->from('batch_download')
            ->where('id = :id')
            ->setParameter('id', $batch->getId()->toRfc4122());

        $entity = $batch->getEntity();

        // Add all document files
        $localPaths = [];
        $documentNumbers = $this->getDocumentNumbers($batch, $entity);
        foreach ($documentNumbers as $documentNr) {
            // Check if the batch still exists. If not, we can stop processing because we are superseded by a new batch.
            $count = (int) $batchQuery->executeQuery()->fetchNumeric();
            if ($count === 0) {
                $this->logger->info('Batch download has been deleted, stopping processing', [
                    'batch_id' => $batch->getId()->toRfc4122(),
                ]);

                return false;
            }

            $document = $this->getDocument($documentNr);
            if (! $document) {
                $this->logger->info('Document no longer exists, skipping', [
                    'batch_id' => $batch->getId()->toRfc4122(),
                    'documentNr' => $documentNr,
                ]);

                continue;
            }

            // Load the document from the storage service locally, and add it to the ZIP archive.
            $localPath = $this->entityStorageService->downloadEntity($document);
            if (! $localPath || ! file_exists($localPath)) {
                $this->logger->error('Could not save document to local path', [
                    'batch_id' => $batch->getId()->toRfc4122(),
                    'document_id' => $document->getId()->toRfc4122(),
                ]);

                continue;
            }

            $zip->addFile($localPath, $this->generateFileName($document));

            $localPaths[] = $localPath;
        }

        // Finished processing
        $zip->close();

        if (empty($localPaths)) {
            $batch->setStatus(BatchDownload::STATUS_FAILED);
            $batch->setSize('0');
            $this->doctrine->persist($batch);
            $this->doctrine->flush();

            return true;
        }

        if ($this->saveZip($zipArchivePath, $batch->getFilename(), $batch)) {
            $batch->setStatus(BatchDownload::STATUS_COMPLETED);
        } else {
            $batch->setStatus(BatchDownload::STATUS_FAILED);
        }

        // Store the documents in the batch
        $batch->setDocuments($documentNumbers);

        // Save new status and size
        $fileSize = filesize($zipArchivePath);
        $batch->setSize(is_int($fileSize) ? strval($fileSize) : '0');   // size == bigint == string
        $this->doctrine->persist($batch);
        $this->doctrine->flush();

        // Remove all temporary local files
        foreach ($localPaths as $localPath) {
            $this->entityStorageService->removeDownload($localPath);
        }
        unlink($zipArchivePath);

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

    public function removeZip(BatchDownload $batch): bool
    {
        try {
            $this->storage->delete($batch->getFilename());

            return true;
        } catch (FilesystemException $e) {
            $this->logger->error('Failed to remove ZIP archive ', [
                'batch' => $batch->getId()->toRfc4122(),
                'path' => $batch->getFilename(),
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function getDocument(string $documentNr): ?Document
    {
        $document = $this->documentRepository->findOneBy(['documentNr' => $documentNr]);
        if (! $document || ! $document->shouldBeUploaded()) {
            return null;
        }

        return $document;
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

    private function generateFileName(Document $document): string
    {
        $fileName = $document->getDocumentNr() . '-' . $document->getFileInfo()->getName();
        if (! str_ends_with(strtolower($fileName), '.pdf')) {
            $fileName .= '.pdf';
        }

        $sanitizer = new FilenameSanitizer($fileName);
        $sanitizer->stripAdditionalCharacters();
        $sanitizer->stripIllegalFilesystemCharacters();
        $sanitizer->stripRiskyCharacters();

        return $sanitizer->getFilename();
    }

    /**
     * @return string[]
     */
    private function getDocumentNumbers(BatchDownload $batch, EntityWithBatchDownload $entity): array
    {
        $documents = $batch->getDocuments();
        if (count($documents) === 0) {
            foreach ($entity->getDocuments() as $document) {
                if (! $document->shouldBeUploaded() || ! $document->isUploaded()) {
                    continue;
                }

                $documents[] = $document->getDocumentNr();
            }
        }

        return $documents;
    }
}
