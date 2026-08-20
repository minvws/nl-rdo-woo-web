<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\PdfPage;

use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\LocalFilesystem;

readonly class PdfPageProcessingContextFactory
{
    public function __construct(
        private EntityStorageService $entityStorageService,
        private LocalFilesystem $localFilesystem,
    ) {
    }

    public function createContext(EntityWithFileInfo $entity, int $pageNumber): ?PdfPageProcessingContext
    {
        if (! $entity->getFileInfo()->isUploaded()) {
            return null;
        }

        $localPath = $this->downloadDocumentToLocalStorage($entity);
        $tempDir = $this->createTempDir();

        return new PdfPageProcessingContext(
            $entity,
            $pageNumber,
            $tempDir,
            $localPath,
        );
    }

    public function teardown(PdfPageProcessingContext $processingContext): void
    {
        $this->entityStorageService->removeDownload($processingContext->getLocalDocument());
        $this->localFilesystem->deleteDirectory($processingContext->getWorkDirPath());
    }

    private function downloadDocumentToLocalStorage(EntityWithFileInfo $entity): string
    {
        $localPath = $this->entityStorageService->downloadEntity($entity);
        if ($localPath === false) {
            throw PdfPageException::forCannotDownload($entity);
        }

        return $localPath;
    }

    private function createTempDir(): string
    {
        $tempDir = $this->localFilesystem->createTempDir();
        if ($tempDir === false) {
            throw PdfPageException::forCannotCreateTempDir();
        }

        return $tempDir;
    }
}
