<?php

declare(strict_types=1);

namespace App\Service\Storage;

use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This class is responsible for storing and retrieving file content based on a Document entity.
 * It is a wrapper around the storage adapter so the rest of the system does not need to know anything about
 * the storage adapter. This way, we can use simple local filesystem or even more complex adapters like S3.
 *
 * Generate a path based on the document id and the file name. This will partition the files in a 2-level deep
 * directory structure based on the document id.
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
 * The root is are the first 2 characters of the SHA256 hash of the document. This allows for better disk spreads as each document
 * is its own directory. The second level is the rest of the SHA256 hash of the document. We separate pages and thumbs in their own
 * directories to prevent name collisions (e.g. a file uploaded called page-1.pdf).
 *
 * Note that the thumbs/page-n.png files are NOT retrieved through this document storage service, but instead through the
 * ThumbnailStorage service. This allows us to separate the storage of the document and the storage of the thumbnails if needed.
 *
 * @SuppressWarnings(TooManyPublicMethods)
 */
class DocumentStorageService
{
    protected FilesystemOperator $storage;
    protected EntityManagerInterface $doctrine;
    protected LoggerInterface $logger;
    protected bool $isLocal;
    protected ?string $documentRoot;

    public function __construct(
        EntityManagerInterface $doctrine,
        FilesystemOperator $storage,
        LoggerInterface $logger,
        bool $isLocal = false,
        string $documentRoot = null
    ) {
        $this->storage = $storage;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->isLocal = $isLocal;

        // Document root should always end with a slash
        $this->documentRoot = $documentRoot ?? '/';
        $this->documentRoot = str_ends_with($this->documentRoot, '/') ? $this->documentRoot : $this->documentRoot . '/';
    }

    /**
     * This will read in-memory. You probably do not want to do this for large files and use retrieveResource() instead.
     *
     * @SuppressWarnings(ErrorControlOperator)
     */
    public function retrieve(string $remotePath, string $localPath): bool
    {
        $stream = $this->retrieveResource($remotePath);
        if (! $stream) {
            return false;
        }

        $file = @fopen($localPath, 'w');
        if (! $file) {
            $this->logger->error('Could not open local path for writing', [
                'local_path' => $localPath,
            ]);

            return false;
        }
        while (! feof($stream)) {
            $data = fread($stream, 1024 * 64);
            if ($data === false) {
                break;
            }

            fwrite($file, $data);
        }

        fclose($stream);
        fclose($file);

        return true;
    }

    /**
     * Reads from the storage adapter and returns a resource. Returns NULL when we cannot read the file.
     *
     * @return resource|null
     */
    public function retrieveResource(string $remotePath)
    {
        try {
            return $this->storage->readStream($remotePath);
        } catch (\Throwable $e) {
            $this->logger->error('Could not read file stream from storage adapter', [
                'remote_path' => $remotePath,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Store a file in the storage adapter and update the document record with the file information.
     *
     * @SuppressWarnings(ErrorControlOperator)
     */
    public function store(\SplFileInfo $localFile, string $remotePath): bool
    {
        // Try and open the file as a stream
        $stream = @fopen($localFile->getPathname(), 'r');
        if (! is_resource($stream)) {
            $this->logger->error('Could not open local file stream', [
                'local_path' => $localFile->getPathname(),
            ]);

            return false;
        }

        // File permissions is taken case of by the adapter configuration settings
        try {
            $this->storage->writeStream($remotePath, $stream);
        } catch (\Throwable $e) {
            $this->logger->error('Could not write file to storage adapter', [
                'remote_path' => $remotePath,
                'local_path' => $localFile->getPathname(),
                'exception' => $e->getMessage(),
            ]);

            // An exception occurred when trying to store the file.
            return false;
        } finally {
            fclose($stream);
        }

        return true;
    }

    /**
     * Returns true when the given remote path exists, false otherwise.
     */
    public function exists(string $remotePath): bool
    {
        try {
            return $this->storage->fileExists($remotePath);
        } catch (\Throwable $e) {
            $this->logger->error('Could not check if file exists in storage adapter', [
                'remote_path' => $remotePath,
                'exception' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Retrieves a given page from the given document into the local path. Returns true on succes, false otherwise.
     */
    public function retrievePage(Document $document, int $pageNr, string $localPath): bool
    {
        $remotePath = $this->generatePagePath($document, $pageNr);

        return $this->retrieve($remotePath, $localPath);
    }

    /**
     * Retrieves the resource/stream of a page of a document. Returns NULL when we cannot read the file.
     *
     * @return resource|null
     */
    public function retrieveResourcePage(Document $document, int $pageNr)
    {
        $remotePath = $this->generatePagePath($document, $pageNr);

        return $this->retrieveResource($remotePath);
    }

    public function storePage(\SplFileInfo $localFile, Document $document, int $pageNr): bool
    {
        $remotePath = $this->generatePagePath($document, $pageNr);

        return $this->store($localFile, $remotePath);
    }

    public function existsPage(Document $document, int $pageNr): bool
    {
        $remotePath = $this->generatePagePath($document, $pageNr);

        return $this->exists($remotePath);
    }

    public function retrieveDocument(Document $document, string $localPath): bool
    {
        $remotePath = $this->generateDocumentPath($document, new \SplFileInfo($document->getFilepath() ?? ''));

        return $this->retrieve($remotePath, $localPath);
    }

    /**
     * @return resource|null
     */
    public function retrieveResourceDocument(Document $document)
    {
        $remotePath = $this->generateDocumentPath($document, new \SplFileInfo($document->getFilepath() ?? ''));

        return $this->retrieveResource($remotePath);
    }

    public function storeDocument(\SplFileInfo $localFile, Document $document): bool
    {
        $remotePath = $this->generateDocumentPath($document, $localFile);

        $result = $this->store($localFile, $remotePath);
        if (! $result) {
            return false;
        }

        // Store file information in document record
        $document->setFilepath($remotePath);
        $document->setFilesize($localFile->getSize());

        $foundationFile = new File($localFile->getPathname());
        $document->setMimetype($foundationFile->getMimeType() ?? '');
        $document->setUploaded(true);

        $this->doctrine->persist($document);
        $this->doctrine->flush();

        return true;
    }

    public function existsDocument(Document $document): bool
    {
        $remotePath = $this->generateDocumentPath($document, new \SplFileInfo($document->getFilepath() ?? ''));

        return $this->exists($remotePath);
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

        // Create a temporary file so store the remote file into
        $localPath = tempnam(sys_get_temp_dir(), 'woopie');
        if (! $localPath) {
            $this->logger->error('Could not create temporary file path', [
                'temp_dir' => sys_get_temp_dir(),
            ]);

            return false;
        }

        // Return the local path when retrieval succeeds
        if ($this->retrieve($remotePath, $localPath)) {
            return $localPath;
        }

        return false;
    }

    public function downloadPage(Document $document, int $pageNr): string|false
    {
        $remotePath = $this->generatePagePath($document, $pageNr);

        return $this->download($remotePath);
    }

    public function downloadDocument(Document $document): string|false
    {
        $remotePath = $this->generateDocumentPath($document, new \SplFileInfo($document->getFilepath() ?? ''));

        return $this->download($remotePath);
    }

    /**
     * Removes the local path IF the file storage is non-local. This means it would delete
     * documents that are stored from a remote storage through download*() methods.
     * Since download*() does not copy the file but actually points to the given file when
     * the filesystem is local, this function will NOT delete the file in that case.
     */
    public function removeDownload(string $localPath): void
    {
        // Don't remove when the storage is local. It would point to the actual stored file
        if ($this->isLocal) {
            return;
        }

        // Unlink since this is a temporary file from a non-local storage
        if (file_exists($localPath)) {
            unlink($localPath);
        }
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
     * Generates the path to a document. It will use the original filename of the file object if it's an uploaded file.
     */
    protected function generateDocumentPath(Document $document, \SplFileInfo $file): string
    {
        $rootPath = $this->getRootPathForDocument($document);

        // Get the filename from the file object. If it's an uploaded file, we need to use the client's original name, not the temporary name.
        $filename = ($file instanceof UploadedFile) ? $file->getClientOriginalName() : $file->getFilename();

        return sprintf('%s/%s', $rootPath, $filename);
    }

    /**
     * Generates the path to a specific page of a document.
     */
    protected function generatePagePath(Document $document, int $pageNr): string
    {
        $rootPath = $this->getRootPathForDocument($document);

        return sprintf('%s/pages/page-%d.pdf', $rootPath, $pageNr);
    }

    /**
     * Returns the list of files in a given path. The filter is a glob pattern.
     *
     * @return FileEntry[]
     */
    public function list(string $remotePath, string $filter = '*'): array
    {
        $listing = $this->storage
            ->listContents($remotePath, false)
            ->filter(function (StorageAttributes $attributes) use ($filter) {
                return fnmatch($filter, $attributes->path());
            })
            ->toArray()
        ;

        $ret = [];
        foreach ($listing as $item) {
            $filesize = $item instanceof FileAttributes ? $item->fileSize() ?? 0 : 0;

            $ret[] = new FileEntry(
                $item->path(),
                $item->type() == StorageAttributes::TYPE_DIRECTORY ? FileEntry::TYPE_DIRECTORY : FileEntry::TYPE_FILE,
                $item->visibility() ?? 'public',
                $item->lastModified() ?? 0,
                $filesize,
            );
        }

        return $ret;
    }
}
