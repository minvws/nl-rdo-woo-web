<?php

declare(strict_types=1);

namespace Shared\Domain\FileStorage\Checker;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;

readonly class FileStorageLister
{
    public function __construct(
        private FilesystemOperator $documentStorage,
        private FilesystemOperator $batchStorage,
    ) {
    }

    public function getFilesystem(FileStorageType $fileStorageType): FilesystemOperator
    {
        return match ($fileStorageType) {
            FileStorageType::DOCUMENT => $this->documentStorage,
            FileStorageType::BATCH => $this->batchStorage,
        };
    }

    /**
     * @return \Generator<string, int>
     */
    public function paths(FileStorageType $fileStorageType): \Generator
    {
        yield from $this->getEntriesForFileSystem(
            $this->getFilesystem($fileStorageType),
        );
    }

    /**
     * @return \Generator<string, int>
     */
    private function getEntriesForFileSystem(FilesystemOperator $filesystem): \Generator
    {
        $files = $filesystem
            ->listContents('/', true)
            ->filter(fn (StorageAttributes $attributes) => $attributes->isFile());

        foreach ($files as $file) {
            /** @var int $size */
            $size = $file[StorageAttributes::ATTRIBUTE_FILE_SIZE];

            yield '/' . $file->path() => $size;
        }
    }
}
