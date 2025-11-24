<?php

declare(strict_types=1);

namespace Shared\Domain\Upload;

use Carbon\CarbonImmutable;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;

readonly class UploadCleaner
{
    private const int CLEANUP_WAIT_DAYS = 10;

    public function __construct(
        private UploadEntityRepository $repository,
        private FilesystemOperator $workingCopyStorage,
        private FilesystemOperator $uploadStorage,
        private UploadService $uploadService,
    ) {
    }

    public function cleanup(): void
    {
        $this->cleanupUploadEntities();
        $this->cleanupOutdatedFilesFromStorage($this->uploadStorage);
        $this->cleanupOutdatedFilesFromStorage($this->workingCopyStorage);
    }

    private function cleanupUploadEntities(): void
    {
        $uploads = $this->repository->findUploadsForCleanup(
            $this->getCutOffDate(),
        );

        foreach ($uploads as $upload) {
            try {
                $this->uploadService->deleteUploadedFile($upload);
            } catch (\Exception) {
                // Ignore, already deleted or never stored
            }

            $this->repository->remove($upload, true);
        }
    }

    private function cleanupOutdatedFilesFromStorage(FilesystemOperator $filesystem): void
    {
        $maxFileDate = $this->getCutOffDate();

        $files = $filesystem
            ->listContents('/', true)
            ->filter(
                static function (StorageAttributes $attributes) use ($maxFileDate): bool {
                    if (! $attributes->isFile() || $attributes->lastModified() === null) {
                        return false;
                    }

                    return CarbonImmutable::createFromTimestamp($attributes->lastModified()) < $maxFileDate;
                }
            );

        foreach ($files as $file) {
            $filesystem->delete($file->path());
        }
    }

    private function getCutOffDate(): CarbonImmutable
    {
        return CarbonImmutable::now()->subDays(self::CLEANUP_WAIT_DAYS);
    }
}
