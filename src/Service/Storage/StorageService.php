<?php

declare(strict_types=1);

namespace App\Service\Storage;

use App\Entity\Document;
use App\Entity\EntityWithFileInfo;
use Psr\Log\LoggerInterface;

/**
 * This class is responsible for storing and retrieving file content based on an entity that implements
 * \App\Entity\EntityWithFileInfo. It is a wrapper around the storage adapter so the rest of the system does not need to
 * know anything about the storage adapter. This way, we can use simple local filesystem or even more complex adapters
 * like S3.
 *
 * Generate a path based on the entity id and the file name. This will partition the files in a 2-level deep
 * directory structure based on the entity id.
 *
 * At this moment, the following structure is used:
 *
 * /13
 *    /1234567890123456789012345678901234567890
 *                                             /file-name.pdf
 *                                             /pages/page-1.pdf
 *                                             /pages/page-2.pdf
 *                                             ...
 *                                             /pages/page-n.pdf
 *                                             /thumbs/thumb.png
 *                                             /thumbs/page-1.png
 *                                             /thumbs/page-2.png
 *                                             ...
 *                                             /thumbs/page-n.png
 *
 * The root is are the first 2 characters of the SHA256 hash of the entity. This allows for better disk spreads as each
 * entity is its own directory. The second level is the rest of the SHA256 hash of the entity. We separate pages and
 * thumbs in their own directories to prevent name collisions (e.g. a file uploaded called page-1.pdf).
 */
abstract class StorageService implements StorageAliveInterface
{
    use HasAlive;

    public function __construct(
        protected RemoteFilesystem $remoteFilesystem,
        protected LocalFilesystem $localFilesystem,
        protected LoggerInterface $logger,
        protected StorageRootPathGenerator $rootPathGenerator,
    ) {
    }

    abstract protected function generatePagePath(EntityWithFileInfo $entity, int $pageNr): string;

    protected function getRootPathForEntity(EntityWithFileInfo $entity): string
    {
        return ($this->rootPathGenerator)($entity);
    }

    protected function getStorage(): RemoteFilesystem
    {
        return $this->remoteFilesystem;
    }

    /**
     * Store a file in the storage adapter.
     */
    protected function doStore(\SplFileInfo $localFile, string $remotePath): bool
    {
        $stream = $this->localFilesystem->createStream($localFile->getPathname(), 'r');
        if (! is_resource($stream)) {
            return false;
        }

        return $this->remoteFilesystem->writeStream($remotePath, $stream);
    }

    protected function doDeleteAllFilesForEntity(EntityWithFileInfo $entity, string $remotePath): bool
    {
        if (! $this->remoteFilesystem->delete($remotePath)) {
            return false;
        }

        $pageCount = $this->getPageCount($entity);
        for ($pageNr = 1; $pageNr <= $pageCount; $pageNr++) {
            $path = $this->generatePagePath($entity, $pageNr);
            if (! $this->remoteFilesystem->delete($path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * This will read in-memory. You probably do not want to do this for large files and use retrieveResource() instead.
     */
    protected function retrieve(string $remotePath, string $localPath): bool
    {
        $remoteStream = $this->remoteFilesystem->readStream($remotePath);
        if (! is_resource($remoteStream)) {
            return false;
        }

        $localStream = $this->localFilesystem->createStream($localPath, 'w');
        if (! is_resource($localStream)) {
            return false;
        }

        if (! $this->localFilesystem->copy($remoteStream, $localStream)) {
            return false;
        }

        return true;
    }

    private function getPageCount(EntityWithFileInfo $entity): int
    {
        $pageCount = $entity->getFileInfo()->isPaginatable()
            ? $entity->getFileInfo()->getPageCount()
            : null;

        // @TODO Remove after Document page count has been removed and replaced by FileInfo page info
        // This acts like a fallback, so if the value is set in the FileInfo it would be used instead of the one on the
        // Document it elf
        if (is_null($pageCount) && $entity instanceof Document) {
            return $entity->getPageCount();
        }

        return $pageCount ?? 0;
    }
}
