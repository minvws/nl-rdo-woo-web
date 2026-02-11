<?php

declare(strict_types=1);

namespace Shared\Service\Storage;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

use function fclose;
use function is_resource;

readonly class RemoteFilesystem
{
    public function __construct(
        protected LoggerInterface $logger,
        protected FilesystemOperator $documentStorage,
    ) {
    }

    /**
     * Reads from the storage adapter and returns a resource. Returns NULL when we cannot read the file.
     *
     * @return resource|null
     */
    public function readStream(string $location)
    {
        try {
            return $this->documentStorage->readStream($location);
        } catch (FilesystemException $e) {
            $this->logger->error('Could not read file stream from storage adapter', [
                'location' => $location,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function directoryExists(string $location): bool
    {
        return $this->documentStorage->directoryExists($location);
    }

    public function createDirectory(string $location): bool
    {
        try {
            $this->documentStorage->createDirectory($location);
        } catch (FilesystemException $e) {
            $this->logger->error('Could not create directory in storage', [
                'location' => $location,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    public function createDirectoryIfNotExist(string $location): bool
    {
        return $this->directoryExists($location) || $this->createDirectory($location);
    }

    public function fileExists(string $location): bool
    {
        try {
            return $this->documentStorage->fileExists($location);
        } catch (FilesystemException $e) {
            $this->logger->error('Could not check if file exists', [
                'location' => $location,
                'exception' => $e->getMessage(),
            ]);
        }

        return false;
    }

    public function fileSize(string $path): int
    {
        try {
            return $this->documentStorage->fileSize($path);
        } catch (FilesystemException $e) {
            $this->logger->error('Could not check file size', [
                'path' => $path,
                'exception' => $e->getMessage(),
            ]);
        }

        return 0;
    }

    public function delete(string $location): bool
    {
        try {
            $this->documentStorage->delete($location);
        } catch (FilesystemException $e) {
            $this->logger->error('Could not delete file from storage for entity', [
                'location' => $location,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * @SuppressWarnings("ErrorControlOperator")
     *
     * @param resource $resource
     */
    public function writeStream(string $location, $resource): bool
    {
        try {
            $this->documentStorage->writeStream($location, $resource);
        } catch (FilesystemException $e) {
            $this->logger->error('Could not write file to storage adapter', [
                'location' => $location,
                'exception' => $e->getMessage(),
            ]);

            return false;
        } finally {
            if (is_resource($resource)) {
                @fclose($resource);
            }
        }

        return true;
    }

    public function write(string $location, string $contents): bool
    {
        try {
            $this->documentStorage->write($location, $contents);
        } catch (FilesystemException $e) {
            $this->logger->error('Could not write contents to storage adapter', [
                'location' => $location,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    public function read(string $location): string|false
    {
        try {
            return $this->documentStorage->read($location);
        } catch (FilesystemException $e) {
            $this->logger->error('Could not read contents from storage adapter', [
                'location' => $location,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
