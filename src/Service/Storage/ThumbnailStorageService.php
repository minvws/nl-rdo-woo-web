<?php

declare(strict_types=1);

namespace App\Service\Storage;

use App\Entity\Document;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * This class is responsible for storing and retrieving thumbnails. See DocumentStorageService for more information.
 */
class ThumbnailStorageService implements StorageAliveInterface
{
    protected FilesystemOperator $storage;
    protected LoggerInterface $logger;

    public function __construct(FilesystemOperator $storage, LoggerInterface $logger)
    {
        $this->storage = $storage;
        $this->logger = $logger;
    }

    /**
     * This will read in-memory. You probably do not want to do this for large files and use retrieveResource() instead.
     */
    public function retrieve(Document $document, int $pageNr = null): ?string
    {
        if ($pageNr) {
            $path = $this->generatePagePath($document, $pageNr);
        } else {
            $path = $this->generateDocumentPath($document);
        }

        try {
            return $this->storage->read($path);
        } catch (\Throwable $e) {
            $this->logger->error('Could not read thumbnail from storage', [
                'exception' => $e->getMessage(),
                'path' => $path,
            ]);

            return null;
        }
    }

    /**
     * Reads from the storage adapter and returns a resource. Returns NULL when we cannot read the file.
     *
     * @return resource|null
     */
    public function retrieveResource(Document $document, int $pageNr = null)
    {
        if ($pageNr) {
            $path = $this->generatePagePath($document, $pageNr);
        } else {
            $path = $this->generateDocumentPath($document);
        }

        try {
            return $this->storage->readStream($path);
        } catch (\Throwable $e) {
            $this->logger->error('Could not read thumbnail from storage', [
                'exception' => $e->getMessage(),
                'path' => $path,
            ]);

            return null;
        }
    }

    /**
     * Store a file in the storage adapter and update the document record with the file information.
     */
    public function store(Document $document, File $file, int $pageNr = null): bool
    {
        if ($pageNr) {
            $path = $this->generatePagePath($document, $pageNr);
        } else {
            $path = $this->generateDocumentPath($document);
        }

        // Create path if not exists
        try {
            if ($this->storage->directoryExists(dirname($path)) === false) {
                // Visibility of the directory is taken care of by the adapter configuration settings
                $this->storage->createDirectory(dirname($path));
            }
        } catch (FilesystemException $e) {
            // Could not create directory
            $this->logger->error('Could not create directory in storage', [
                'document' => $document->getId(),
                'path' => $path,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        // Open the file as a SplFileObject, so we can stream it to the storage adapter
        $stream = fopen($file->getRealPath(), 'r');
        if (! is_resource($stream)) {
            $this->logger->error('Could not open file stream', [
                'document' => $document->getId(),
                'path' => $path,
                'file' => $file->getFilename(),
            ]);
        }

        // File permissions is taken case of by the adapter configuration settings
        try {
            $this->storage->writeStream($path, $stream);
        } catch (\Throwable $e) {
            // An exception occurred when trying to store the file.

            $this->logger->error('Could not write thumbnail to storage', [
                'document' => $document->getId(),
                'file' => $file->getPathname(),
                'path' => $path,
                'exception' => $e->getMessage(),
            ]);

            return false;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return true;
    }

    /**
     * Returns true if the document thumbnail or pageNr exists.
     */
    public function exists(Document $document, int $pageNr = null): bool
    {
        if ($pageNr) {
            $path = $this->generatePagePath($document, $pageNr);
        } else {
            $path = $this->generateDocumentPath($document);
        }

        $this->logger->info('Path: ' . $path);

        // Create path if not exists
        try {
            return $this->storage->fileExists($path);
        } catch (FilesystemException $e) {
            // Could not create directory
            $this->logger->error('Could not check if file exists', [
                'document' => $document->getId(),
                'path' => $path,
                'exception' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Returns the filesize in bytes, or 0 when file is not found (or empty, not readable etc).
     */
    public function fileSize(Document $document, int $pageNr = null): int
    {
        if ($pageNr) {
            $path = $this->generatePagePath($document, $pageNr);
        } else {
            $path = $this->generateDocumentPath($document);
        }

        $this->logger->info('Path: ' . $path);

        // Create path if not exists
        try {
            return $this->storage->fileSize($path);
        } catch (FilesystemException $e) {
            // Could not create directory
            $this->logger->error('Could not check file size', [
                'document' => $document->getId(),
                'path' => $path,
                'exception' => $e->getMessage(),
            ]);
        }

        return 0;
    }

    public function deleteAllThumbsForDocument(Document $document): bool
    {
        try {
            $path = $this->generateDocumentPath($document);
            $this->storage->delete($path);

            for ($pageNr = 1; $pageNr <= $document->getPageCount(); $pageNr++) {
                $path = $this->generatePagePath($document, $pageNr);
                $this->storage->delete($path);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Could not delete thumbnails from storage', [
                'exception' => $e->getMessage(),
                'path' => $path ?? '',
            ]);

            return false;
        }

        return true;
    }

    /**
     * Returns the root path of a document. Normally, this is /{prefix}/{suffix}, where prefix are the first two characters of the
     * SHA256 hash, and suffix is the rest of the SHA256 hash.
     */
    protected function getRootPathForDocument(Document $document): string
    {
        $documentId = (string) $document->getId();
        $hash = hash('sha256', $documentId);

        $prefix = substr($hash, 0, 2);
        $suffix = substr($hash, 2);

        return "/$prefix/$suffix";
    }

    /**
     * Generates the path to a specific page of a document.
     */
    protected function generatePagePath(Document $document, int $pageNr): string
    {
        $rootPath = $this->getRootPathForDocument($document);

        return sprintf('%s/thumbs/thumb-page-%d.png', $rootPath, $pageNr);
    }

    /**
     * Generates the path to a document.
     */
    protected function generateDocumentPath(Document $document): string
    {
        $rootPath = $this->getRootPathForDocument($document);

        return sprintf('%s/thumbs/thumb.png', $rootPath);
    }

    public function isAlive(): bool
    {
        $suffix = hash('sha256', random_bytes(32));

        try {
            $this->storage->write("healthcheck.{$suffix}", $suffix);
            $content = $this->storage->read("healthcheck.{$suffix}");
            $this->storage->delete("healthcheck.{$suffix}");
        } catch (\Exception) {
            return false;
        }

        return $content == $suffix;
    }
}
