<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Entity\Document;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use App\Service\Worker\Pdf\Tools\FileUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Extractor that will extract a single page as a PDF file from a multi-paged PDF document.
 */
class PageExtractor implements PageExtractorInterface
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
        if (! $forceRefresh && $this->documentStorage->existsPage($document, $pageNr)) {
            // Page already exists, and we are allowed to use it
            return;
        }

        $localPath = $this->documentStorage->downloadDocument($document);
        if (! $localPath) {
            $this->logger->error('cannot download document from storage', [
                'document' => $document->getId(),
            ]);

            return;
        }

        $tempDir = $this->fileUtils->createTempDir();
        $targetPath = $tempDir . '/page.pdf';

        $params = ['/usr/bin/pdftk', $localPath, 'cat', $pageNr, 'output', $targetPath];
        $this->logger->debug('EXEC: ' . join(' ', $params));
        $process = new Process($params);
        $process->run();

        // Remove local file
        $this->documentStorage->removeDownload($localPath);

        if (! $process->isSuccessful()) {
            $this->logger->error('Failed to fetch PDF page: ', [
                'document' => $document->getId(),
                'pageNr' => $pageNr,
                'sourcePath' => $localPath,
                'targetPath' => $targetPath,
                'error_output' => $process->getErrorOutput(),
            ]);

            $this->fileUtils->deleteTempDirectory($tempDir);

            return;
        }

        $this->documentStorage->storePage(new \SplFileInfo($targetPath), $document, $pageNr);

        $this->fileUtils->deleteTempDirectory($tempDir);
    }
}
