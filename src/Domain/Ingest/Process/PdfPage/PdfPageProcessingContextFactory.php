<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\PdfPage;

use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Stats\WorkerStatsService;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\LocalFilesystem;

readonly class PdfPageProcessingContextFactory
{
    public function __construct(
        private EntityStorageService $entityStorageService,
        private WorkerStatsService $statsService,
        private LocalFilesystem $localFilesystem,
    ) {
    }

    public function createContext(EntityWithFileInfo $entity, int $pageNumber): PdfPageProcessingContext
    {
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
        /** @var string|false $localPath */
        $localPath = $this->statsService->measure(
            'download.entity',
            fn (): string|false => $this->entityStorageService->downloadEntity($entity),
        );

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
