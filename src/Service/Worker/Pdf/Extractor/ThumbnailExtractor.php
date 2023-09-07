<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Entity\Document;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use App\Service\Worker\Pdf\Tools\FileUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Process\Process;

/**
 * Extractor that will extract the thumbnail of a single-page PDF file.
 */
class ThumbnailExtractor implements PageExtractorInterface
{
    protected LoggerInterface $logger;
    protected ThumbnailStorageService $thumbnailStorage;
    protected DocumentStorageService $documentStorage;
    protected FileUtils $fileUtils;

    public function __construct(LoggerInterface $logger, ThumbnailStorageService $thumbnailStorage, DocumentStorageService $documentStorage)
    {
        $this->logger = $logger;
        $this->thumbnailStorage = $thumbnailStorage;
        $this->documentStorage = $documentStorage;

        $this->fileUtils = new FileUtils();
    }

    public function extract(Document $document, int $pageNr, bool $forceRefresh): void
    {
        if (! $forceRefresh && $this->thumbnailStorage->exists($document, $pageNr)) {
            // Thumbnail already exists, and we are allowed to use it
            return;
        }

        $tempDir = $this->fileUtils->createTempDir();

        $localPath = $this->documentStorage->downloadPage($document, $pageNr);
        if (! $localPath) {
            $this->logger->error('cannot download page from storage', [
                'document' => $document->getId(),
                'page' => $pageNr,
            ]);

            return;
        }

        $targetPath = $tempDir . '/thumb';  // pdftoppm will add the extension

        // Create thumbnail
        $params = ['/usr/bin/pdftoppm', '-png', '-scale-to', '200', '-r', '150', '-singlefile', $localPath, $targetPath];
        $this->logger->debug('EXEC: ' . join(' ', $params));
        $process = new Process($params);
        $process->run();

        $this->documentStorage->removeDownload($localPath);

        if (! $process->isSuccessful()) {
            $this->logger->error('Failed to create thumbnail for document', [
                'document' => $document->getId(),
                'page' => $pageNr,
                'localPath' => $localPath,
                'targetPath' => $targetPath,
                'error_output' => $process->getErrorOutput(),
            ]);

            $this->fileUtils->deleteTempDirectory($tempDir);

            return;
        }

        $file = new File($targetPath . '.png');
        $this->thumbnailStorage->store($document, $file, $pageNr);

        $this->fileUtils->deleteTempDirectory($tempDir);
    }
}
