<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\LocalFilesystem;
use App\Service\Storage\ThumbnailStorageService;
use App\Service\Worker\Pdf\Tools\Pdftoppm\PdftoppmService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Extractor that will extract the thumbnail of a single-page PDF file.
 */
class ThumbnailExtractor implements PageExtractorInterface
{
    public function __construct(
        protected LoggerInterface $logger,
        protected ThumbnailStorageService $thumbnailStorage,
        protected EntityStorageService $entityStorageService,
        protected LocalFilesystem $localFilesystem,
        protected PdftoppmService $pdftoppmService,
    ) {
    }

    public function extract(EntityWithFileInfo $entity, int $pageNr, bool $forceRefresh): void
    {
        // TODO: Cache is removed for #2142, to be improved and restored in #2144
        unset($forceRefresh);

        $tempDir = $this->localFilesystem->createTempDir();
        if ($tempDir === false) {
            return;
        }

        $sourcePdf = $this->entityStorageService->downloadPage($entity, $pageNr);
        if ($sourcePdf === false) {
            $this->logger->error('Cannot download page from storage', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'pageNr' => $pageNr,
            ]);

            return;
        }

        $targetPath = $tempDir . '/thumb';  // pdftoppm will add the extension

        $pdftoppmResult = $this->pdftoppmService->createThumbnail($sourcePdf, $targetPath);

        $this->entityStorageService->removeDownload($sourcePdf);

        if ($pdftoppmResult->isFailed()) {
            $this->logger->error('Failed to create thumbnail for entity', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'pageNr' => $pageNr,
                'sourcePath' => $pdftoppmResult->sourcePdf,
                'targetPath' => $pdftoppmResult->targetPath,
                'error_output' => $pdftoppmResult->errorMessage,
            ]);

            $this->localFilesystem->deleteDirectory($tempDir);

            return;
        }

        $this->thumbnailStorage->store($entity, new File($targetPath . '.png'), $pageNr);

        $this->localFilesystem->deleteDirectory($tempDir);
    }
}
