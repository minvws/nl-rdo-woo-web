<?php

declare(strict_types=1);

namespace App\Service\Storage;

use App\Domain\Ingest\Content\Event\EntityFileUpdateEvent;
use App\Entity\EntityWithFileInfo;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * This class is responsible for storing and retrieving files attached to entities using FileInfo. See StorageService
 * for more information.
 *
 * @SuppressWarnings(TooManyPublicMethods)
 */
class EntityStorageService extends StorageService
{
    private string $documentRoot;

    public function __construct(
        RemoteFilesystem $remoteFilesystem,
        LocalFilesystem $localFilesystem,
        LoggerInterface $logger,
        StorageRootPathGenerator $rootPathGenerator,
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $doctrine,
        private bool $isLocal = false,
        ?string $documentRoot = null,
    ) {
        parent::__construct($remoteFilesystem, $localFilesystem, $logger, $rootPathGenerator);

        // Document root should always end with a slash
        $documentRoot = $documentRoot ?? '/';
        $this->documentRoot = str_ends_with($documentRoot, '/') ? $documentRoot : $documentRoot . '/';
    }

    /**
     * Store a file in the storage adapter.
     */
    public function store(\SplFileInfo $localFile, string $remotePath): bool
    {
        return $this->doStore($localFile, $remotePath);
    }

    /**
     * Retrieves the resource/stream of a page of an entity. Returns NULL when we cannot read the file.
     *
     * @return resource|null
     */
    public function retrieveResourcePage(EntityWithFileInfo $entity, int $pageNr)
    {
        $remotePath = $this->generatePagePath($entity, $pageNr);

        return $this->remoteFilesystem->readStream($remotePath);
    }

    public function storePage(\SplFileInfo $localFile, EntityWithFileInfo $entity, int $pageNr): bool
    {
        $remotePath = $this->generatePagePath($entity, $pageNr);

        return $this->doStore($localFile, $remotePath);
    }

    /**
     * @return resource|null
     */
    public function retrieveResourceEntity(EntityWithFileInfo $entity)
    {
        $remotePath = $this->generateEntityPath($entity, new \SplFileInfo($entity->getFileInfo()->getPath() ?? ''));

        return $this->remoteFilesystem->readStream($remotePath);
    }

    public function storeEntity(\SplFileInfo $localFile, EntityWithFileInfo $entity, bool $flush = true): bool
    {
        if ($entity->getFileInfo()->getHash() !== null) {
            $this->messageBus->dispatch(
                EntityFileUpdateEvent::forEntity($entity)
            );
        }

        $remotePath = $this->generateEntityPath($entity, $localFile);

        $result = $this->doStore($localFile, $remotePath);
        if (! $result) {
            return false;
        }

        $file = $entity->getFileInfo();
        $file->setPath($remotePath);
        $file->setSize($localFile->getSize());

        $this->setHash($entity, $localFile->getPathname(), false);

        $foundationFile = new File($localFile->getPathname());
        $file->setMimetype($foundationFile->getMimeType() ?? '');
        $file->setUploaded(true);

        $this->doctrine->persist($entity);

        if ($flush) {
            $this->doctrine->flush();
        }

        return true;
    }

    public function setHash(EntityWithFileInfo $entity, string $path, bool $flush = true): void
    {
        if (! is_readable($path)) {
            throw new \RuntimeException('Cannot read file for hash generation: ' . $path);
        }

        $hash = hash_file('sha256', $path);
        if ($hash === false) {
            throw new \RuntimeException('Cannot generate hash for file: ' . $path);
        }

        $fileInfo = $entity->getFileInfo();
        $fileInfo->setHash($hash);

        $this->doctrine->persist($entity);
        if ($flush) {
            $this->doctrine->flush();
        }
    }

    /**
     * Downloads a given remote file to a local path. If the path is already local, it will just return the actual path.
     * DO NOT REMOVE the given file, but use the removeDownload() method for this, as this will check if the path is local or not.
     */
    public function download(string $remotePath): string|false
    {
        // Return remote filepath when the filesystem is local.
        if ($this->isLocal) {
            return $this->documentRoot . $remotePath;
        }

        $localPath = $this->localFilesystem->createTempFile();
        if ($localPath === false) {
            return false;
        }

        if (! $this->retrieve($remotePath, $localPath)) {
            $this->localFilesystem->deleteFile($localPath);

            return false;
        }

        return $localPath;
    }

    public function downloadPage(EntityWithFileInfo $entity, int $pageNr): string|false
    {
        $remotePath = $this->generatePagePath($entity, $pageNr);

        return $this->download($remotePath);
    }

    public function downloadEntity(EntityWithFileInfo $entity): string|false
    {
        $remotePath = $this->generateEntityPath($entity, new \SplFileInfo($entity->getFileInfo()->getPath() ?? ''));

        return $this->download($remotePath);
    }

    /**
     * Removes the local path IF the file storage is non-local. This means it would delete
     * documents that are stored from a remote storage through download*() methods.
     * Since download*() does not copy the file but actually points to the given file when
     * the filesystem is local, this function will NOT delete the file in that case.
     */
    public function removeDownload(string $localPath, bool $forceLocalDelete = false): void
    {
        // Don't remove when the storage is local. It would point to the actual stored file
        if ($this->isLocal && ! $forceLocalDelete) {
            return;
        }

        // Delete file since this is a temporary file from a non-local storage
        $this->localFilesystem->deleteFile($localPath);
    }

    public function deleteAllFilesForEntity(EntityWithFileInfo $entity): bool
    {
        if (! $entity->getFileInfo()->isUploaded()) {
            return true;
        }

        $this->messageBus->dispatch(
            EntityFileUpdateEvent::forEntity($entity)
        );

        $path = $this->generateEntityPath($entity, new \SplFileInfo($entity->getFileInfo()->getPath() ?? ''));

        return $this->doDeleteAllFilesForEntity($entity, $path);
    }

    public function removeFileForEntity(EntityWithFileInfo $entity): bool
    {
        $this->messageBus->dispatch(
            EntityFileUpdateEvent::forEntity($entity)
        );

        $path = $this->generateEntityPath($entity, new \SplFileInfo($entity->getFileInfo()->getPath() ?? ''));

        return $this->remoteFilesystem->delete($path);
    }

    protected function generatePagePath(EntityWithFileInfo $entity, int $pageNr): string
    {
        $rootPath = $this->getRootPathForEntity($entity);

        return sprintf('%s/pages/page-%d.pdf', $rootPath, $pageNr);
    }

    protected function generateEntityPath(EntityWithFileInfo $entity, \SplFileInfo $file): string
    {
        $rootPath = $this->getRootPathForEntity($entity);

        $filename = $file instanceof UploadedFile
            ? $file->getClientOriginalName()
            : $file->getFilename();

        return sprintf('%s/%s', $rootPath, $filename);
    }
}
