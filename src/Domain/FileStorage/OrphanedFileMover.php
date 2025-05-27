<?php

declare(strict_types=1);

namespace App\Domain\FileStorage;

use App\Domain\FileStorage\Checker\FileStorageLister;
use App\Domain\FileStorage\Checker\FileStorageType;
use App\Domain\FileStorage\Checker\OrphanedPaths;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;

readonly class OrphanedFileMover
{
    public function __construct(
        private FileStorageLister $fileStorageLister,
        private S3Client $s3Client,
    ) {
    }

    public function move(OrphanedPaths $orphanedPaths, string $targetBucket, ?callable $progressTicker = null): void
    {
        $adapter = new AwsS3V3Adapter($this->s3Client, $targetBucket);
        $targetFilesystem = new Filesystem($adapter);

        foreach ($orphanedPaths->paths as $storageType => $paths) {
            foreach ($paths as $path) {
                $this->streamCopyAndDelete($storageType, $path, $targetFilesystem);

                if ($progressTicker !== null) {
                    $progressTicker();
                }
            }
        }
    }

    private function streamCopyAndDelete(string $storageType, string $path, Filesystem $targetFilesystem): void
    {
        $sourceFilesystem = $this->fileStorageLister->getFilesystem(
            FileStorageType::from($storageType),
        );

        $targetFilesystem->writeStream(
            $storageType . '/' . $path,
            $sourceFilesystem->readStream($path),
        );

        $sourceFilesystem->delete($path);
    }
}
