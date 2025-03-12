<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Service\DownloadFilenameGenerator;
use App\Service\FilenameSanitizer;
use App\Service\Storage\EntityStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore This class is to be refactored in woo-3593.
 */
readonly class BatchDownloadZipGenerator
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private EntityStorageService $entityStorageService,
        private LoggerInterface $logger,
        private DownloadFilenameGenerator $filenameGenerator,
        private BatchDownloadStorage $storage,
        private BatchDownloadService $batchDownloadService,
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
        $scope = BatchDownloadScope::fromBatch($batch);
        $type = $this->batchDownloadService->getType($scope);

        if (! $type->isAvailableForBatchDownload($scope)) {
            return $this->fail($batch);
        }

        /** @var Document[] $documents */
        $documents = $type->getDocumentsQuery($scope)->getQuery()->getResult();
        if (count($documents) === 0) {
            return $this->fail($batch);
        }

        $zipArchivePath = sprintf('%s/archive-%s.zip', sys_get_temp_dir(), $batch->getId()->toBase58());
        $zip = new \ZipArchive();
        $zip->open($zipArchivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $batchQuery = $this->doctrine->getConnection()->createQueryBuilder();
        $batchQuery
            ->select('COUNT(*)')
            ->from('batch_download')
            ->where('id = :id')
            ->setParameter('id', $batch->getId()->toRfc4122());

        // Add all document files
        $localPaths = [];
        foreach ($documents as $document) {
            // Check if the batch still exists. If not, we can stop processing because we are superseded by a new batch.
            $count = (int) $batchQuery->executeQuery()->fetchNumeric();
            if ($count === 0) {
                $this->logger->info('Batch download has been deleted, stopping processing', [
                    'batch_id' => $batch->getId()->toRfc4122(),
                ]);

                $zip->unchangeAll();
                $zip->close();
                $this->removeAllTemporaryLocalFiles($localPaths);

                return false;
            }

            // Load the document from the storage service locally, and add it to the ZIP archive.
            $localPath = $this->entityStorageService->downloadEntity($document);
            if (! $localPath || ! file_exists($localPath)) {
                $this->logger->error('Could not save document to local path', [
                    'batch_id' => $batch->getId()->toRfc4122(),
                    'document_id' => $document->getId()->toRfc4122(),
                ]);

                $zip->unchangeAll();
                $zip->close();
                $this->removeAllTemporaryLocalFiles($localPaths);

                return $this->fail($batch);
            }

            $zip->addFile(
                $localPath,
                $this->filenameGenerator->getFileName($document),
            );

            $localPaths[] = $localPath;
        }

        // Finished processing
        $zip->close();
        $this->removeAllTemporaryLocalFiles($localPaths);

        $batchFileName = $this->getFilename($type->getFileBaseName($scope));
        if (count($localPaths) === 0 || ! $this->storage->add($zipArchivePath, $batchFileName)) {
            unlink($zipArchivePath);

            return $this->fail($batch);
        }

        $fileSize = filesize($zipArchivePath);
        $batch->complete(
            filename: $batchFileName,
            size: is_int($fileSize) ? strval($fileSize) : '0',
            fileCount: count($localPaths),
        );
        $this->doctrine->persist($batch);
        $this->doctrine->flush();

        unlink($zipArchivePath);

        return true;
    }

    private function getFilename(string $basename): string
    {
        $filename = sprintf(
            '%s.zip',
            $basename,
        );

        $sanitizer = new FilenameSanitizer($filename);
        $sanitizer->stripAdditionalCharacters();
        $sanitizer->stripIllegalFilesystemCharacters();
        $sanitizer->stripRiskyCharacters();

        return $sanitizer->getFilename();
    }

    private function fail(BatchDownload $batch): bool
    {
        $batch->markAsFailed();
        $this->doctrine->persist($batch);
        $this->doctrine->flush();

        return false;
    }

    /**
     * @param string[] $localPaths
     */
    private function removeAllTemporaryLocalFiles(array $localPaths): void
    {
        foreach ($localPaths as $localPath) {
            $this->entityStorageService->removeDownload($localPath);
        }
    }
}
