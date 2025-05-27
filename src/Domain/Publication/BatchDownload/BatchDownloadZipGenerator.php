<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload;

use App\Domain\Publication\BatchDownload\Archiver\ArchiveNamer;
use App\Domain\Publication\BatchDownload\Archiver\BatchArchiver;
use App\Domain\Publication\BatchDownload\Archiver\BatchArchiverResult;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class BatchDownloadZipGenerator
{
    private const CHECK_STATUS_EVERY_X_DOCUMENTS = 10;

    public function __construct(
        private EntityManagerInterface $doctrine,
        private LoggerInterface $logger,
        private BatchDownloadService $batchDownloadService,
        private BatchArchiver $batchArchiver,
        private ArchiveNamer $archiveNamer,
    ) {
    }

    /**
     * Generates a ZIP archive for the given batch download. Returns true on success (or already created), false otherwise.
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
        if ($documents === []) {
            return $this->fail($batch);
        }

        $batchFileName = $this->archiveNamer->getArchiveName($type->getFileBaseName($scope), $batch);

        $this->batchArchiver->start($type, $batch, $batchFileName);

        $this->setBatchFilename($batch, $batchFileName);

        $documentCount = 0;
        foreach ($documents as $document) {
            // Check if the batch still exists. If not, we can stop processing because we are superseded by a new batch.
            if (! $this->batchDownloadService->exists($batch)) {
                $this->logger->info('Batch download has been deleted, stopping processing', [
                    'batch_id' => $batch->getId()->toRfc4122(),
                ]);

                return false;
            }

            if ($documentCount % self::CHECK_STATUS_EVERY_X_DOCUMENTS === 0 && $this->isNoLongerPending($batch)) {
                $this->logger->info('Batch download status is no longer pending, stopping batch archive generation', [
                    'batch_id' => $batch->getId()->toRfc4122(),
                ]);

                $this->batchArchiver->cleanup();

                return false;
            }

            if (! $this->batchArchiver->addDocument($document)) {
                $this->logger->error('Could not add document to archive', [
                    'batch_id' => $batch->getId()->toRfc4122(),
                    'document_id' => $document->getId()->toRfc4122(),
                ]);

                return $this->fail($batch);
            }

            $documentCount++;
        }

        $result = $this->batchArchiver->finish();
        if ($result === false) {
            return $this->fail($batch);
        }

        return $this->success($batch, $result);
    }

    private function success(BatchDownload $batch, BatchArchiverResult $result): bool
    {
        if ($this->isNoLongerPending($batch)) {
            $this->batchArchiver->cleanup();

            return false;
        }

        $batch->complete(
            filename: $result->filename,
            size: $result->size,
            fileCount: $result->fileCount,
        );
        $this->doctrine->persist($batch);
        $this->doctrine->flush();

        return true;
    }

    private function fail(BatchDownload $batch): false
    {
        $batch->markAsFailed();
        $this->doctrine->persist($batch);
        $this->doctrine->flush();

        return false;
    }

    private function setBatchFilename(BatchDownload $batch, string $filename): void
    {
        $batch->setFilename($filename);
        $this->doctrine->persist($batch);
        $this->doctrine->flush();
    }

    private function isNoLongerPending(BatchDownload $batch): bool
    {
        $this->doctrine->refresh($batch);

        return ! $batch->getStatus()->isPending();
    }
}
