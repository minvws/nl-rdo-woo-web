<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

use function fclose;
use function fopen;
use function is_resource;

readonly class BatchDownloadStorage
{
    public function __construct(
        private FilesystemOperator $filesystem,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return false|resource
     */
    public function getFileStreamForBatch(BatchDownload $batch)
    {
        try {
            return $this->filesystem->readStream(
                $batch->getFilename(),
            );
        } catch (FilesystemException $e) {
            $this->logger->error('Failed open ZIP archive ', [
                'batch' => $batch->getId()->toRfc4122(),
                'path' => $batch->getFilename(),
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function removeFileForBatch(BatchDownload $batch): bool
    {
        if ($batch->getFilename() === '') {
            return true;
        }

        try {
            $this->filesystem->delete(
                $batch->getFilename(),
            );

            return true;
        } catch (FilesystemException $e) {
            $this->logger->error('Failed to remove ZIP archive', [
                'batch' => $batch->getId()->toRfc4122(),
                'path' => $batch->getFilename(),
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function add(string $sourcePath, string $destinationPath): bool
    {
        $stream = fopen($sourcePath, 'rb');
        if (! is_resource($stream)) {
            $this->logger->error('Could not open zip file stream', [
                'path' => $sourcePath,
            ]);

            return false;
        }

        try {
            $this->filesystem->writeStream($destinationPath, $stream);
        } catch (FilesystemException $e) {
            $this->logger->error('Failed to move ZIP archive to storage', [
                'source_path' => $sourcePath,
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
