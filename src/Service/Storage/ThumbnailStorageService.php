<?php

declare(strict_types=1);

namespace App\Service\Storage;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\EntityWithFileInfo;
use Psr\Log\LoggerInterface;

/**
 * This class is responsible for storing and retrieving thumbnails attach to entities using FileInfo. See StorageService
 * for more information.
 */
class ThumbnailStorageService extends StorageService
{
    public function __construct(
        protected RemoteFilesystem $remoteFilesystem,
        protected LocalFilesystem $localFilesystem,
        protected LoggerInterface $logger,
        protected StorageRootPathGenerator $rootPathGenerator,
        protected int $thumbnailLimit,
    ) {
        parent::__construct($remoteFilesystem, $localFilesystem, $logger, $rootPathGenerator);
    }

    /**
     * Reads from the storage adapter and returns a resource. Returns NULL when we cannot read the file.
     *
     * @return resource|null
     */
    public function retrieveResource(EntityWithFileInfo $entity, int $pageNr)
    {
        $remotePath = $this->generateThumbPath($entity, $pageNr);

        return $this->remoteFilesystem->readStream($remotePath);
    }

    /**
     * Store a file in the storage adapter.
     */
    public function store(EntityWithFileInfo $entity, \SplFileInfo $localFile, int $pageNr): bool
    {
        $remotePath = $this->generateThumbPath($entity, $pageNr);

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
        $remotePath = $this->generateThumbPath($entity, $pageNr);

        return $this->remoteFilesystem->fileExists($remotePath);
    }

    /**
     * Returns the filesize in bytes, or 0 when file is not found (or empty, not readable etc).
     */
    public function fileSize(EntityWithFileInfo $entity, int $pageNr): int
    {
        $path = $this->generateThumbPath($entity, $pageNr);

        return $this->remoteFilesystem->fileSize($path);
    }

    public function deleteAllThumbsForEntity(EntityWithFileInfo $entity): bool
    {
        if ($entity->getFileInfo()->hasPages()) {
            $pageCount = $this->getPageCount($entity);
            for ($pageNr = 1; $pageNr <= $pageCount && $pageNr <= $this->thumbnailLimit; $pageNr++) {
                $path = $this->generateThumbPath($entity, $pageNr);
                if (! $this->remoteFilesystem->delete($path)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function generateThumbPath(EntityWithFileInfo $entity, int $pageNr): string
    {
        $rootPath = $this->getRootPathForEntity($entity);

        return sprintf('%s/thumbs/thumb-page-%d.png', $rootPath, $pageNr);
    }

    private function getPageCount(EntityWithFileInfo $entity): int
    {
        $pageCount = $entity->getFileInfo()->isPaginatable()
            ? $entity->getFileInfo()->getPageCount()
            : null;

        // This acts like a fallback, so if the value is set in the FileInfo it would be used instead of the one on the
        // Document itself
        if (is_null($pageCount) && $entity instanceof Document) {
            return $entity->getPageCount();
        }

        return $pageCount ?? 0;
    }
}
