<?php

declare(strict_types=1);

namespace App\Service\Storage;

use App\Entity\EntityWithFileInfo;

/**
 * This class is responsible for storing and retrieving thumbnails attach to entities using FileInfo. See StorageService
 * for more information.
 */
class ThumbnailStorageService extends StorageService
{
    /**
     * Reads from the storage adapter and returns a resource. Returns NULL when we cannot read the file.
     *
     * @return resource|null
     */
    public function retrieveResource(EntityWithFileInfo $entity, int $pageNr)
    {
        $remotePath = $this->generatePagePath($entity, $pageNr);

        return $this->remoteFilesystem->readStream($remotePath);
    }

    /**
     * Store a file in the storage adapter.
     */
    public function store(EntityWithFileInfo $entity, \SplFileInfo $localFile, int $pageNr): bool
    {
        $remotePath = $this->generatePagePath($entity, $pageNr);

        if (! $this->remoteFilesystem->createDirectoryIfNotExist(dirname($remotePath))) {
            return false;
        }

        return $this->doStore($localFile, $remotePath);
    }

    /**
     * Returns true if the entity's thumbnail for the pageNr exists.
     */
    public function exists(EntityWithFileInfo $entity, int $pageNr): bool
    {
        $remotePath = $this->generatePagePath($entity, $pageNr);

        return $this->remoteFilesystem->fileExists($remotePath);
    }

    /**
     * Returns the filesize in bytes, or 0 when file is not found (or empty, not readable etc).
     */
    public function fileSize(EntityWithFileInfo $entity, int $pageNr): int
    {
        $path = $this->generatePagePath($entity, $pageNr);

        return $this->remoteFilesystem->fileSize($path);
    }

    public function deleteAllThumbsForEntity(EntityWithFileInfo $entity): bool
    {
        $path = $this->generateEntityPath($entity);

        return $this->doDeleteAllFilesForEntity($entity, $path);
    }

    protected function generatePagePath(EntityWithFileInfo $entity, int $pageNr): string
    {
        $rootPath = $this->getRootPathForEntity($entity);

        return sprintf('%s/thumbs/thumb-page-%d.png', $rootPath, $pageNr);
    }

    protected function generateEntityPath(EntityWithFileInfo $entity): string
    {
        $rootPath = $this->getRootPathForEntity($entity);

        return sprintf('%s/thumbs/thumb.png', $rootPath);
    }
}
