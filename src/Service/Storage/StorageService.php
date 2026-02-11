<?php

declare(strict_types=1);

namespace Shared\Service\Storage;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\EntityWithFileInfo;
use SplFileInfo;

use function is_resource;

/**
 * This class is responsible for storing and retrieving file content based on an entity that implements
 * \Shared\Domain\Publication\EntityWithFileInfo. It is a wrapper around the storage adapter so the rest of the system does
 * not need to know anything about the storage adapter. This way, we can use simple local filesystem or even more
 * complex adapters like S3.
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
    protected function doStore(SplFileInfo $localFile, string $remotePath): bool
    {
        $stream = $this->localFilesystem->createStream($localFile->getPathname(), 'r');
        if (! is_resource($stream)) {
            return false;
        }

        return $this->remoteFilesystem->writeStream($remotePath, $stream);
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
}
