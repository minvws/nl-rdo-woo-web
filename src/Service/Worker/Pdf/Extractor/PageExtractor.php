<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Stats\WorkerStatsService;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\LocalFilesystem;
use App\Service\Storage\ThumbnailStorageService;
use App\Service\Worker\Pdf\Tools\Pdftk\PdftkPageExtractResult;
use App\Service\Worker\Pdf\Tools\Pdftk\PdftkService;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

/**
 * Extractor that will extract a single page as a PDF file from a multi-paged PDF entity.
 */
readonly class PageExtractor implements PageExtractorInterface
{
    public function __construct(
        protected LoggerInterface $logger,
        protected PdftkService $pdftkService,
        protected ThumbnailStorageService $thumbnailStorage,
        protected EntityStorageService $entityStorageService,
        protected WorkerStatsService $statsService,
        protected LocalFilesystem $localFilesystem,
    ) {
    }

    public function extract(EntityWithFileInfo $entity, int $pageNr, bool $forceRefresh): void
    {
        Assert::true($entity->getFileInfo()->isPaginatable(), 'Entity is not paginatable');

        // TODO: Cache is removed for #2142, to be improved and restored in #2144
        unset($forceRefresh);

        /** @var string|false $localPath */
        $localPath = $this->statsService->measure(
            'download.entity',
            fn (): string|false => $this->entityStorageService->downloadEntity($entity),
        );

        if ($localPath === false) {
            $this->logger->error('cannot download entity from storage', [
                'id' => $entity->getId(),
                'class' => $entity::class,
            ]);

            return;
        }

        $tempDir = $this->localFilesystem->createTempDir();
        if ($tempDir === false) {
            $this->logger->error('Failed creating temp dir', [
                'id' => $entity->getId(),
                'class' => $entity::class,
            ]);

            return;
        }
        $targetPath = $tempDir . '/page.pdf';

        /** @var PdftkPageExtractResult $pdftkPageExtractResult */
        $pdftkPageExtractResult = $this->statsService->measure(
            'pdftk.extractPage',
            fn (): PdftkPageExtractResult => $this->pdftkService->extractPage($localPath, $pageNr, $targetPath),
        );

        // Remove local file
        $this->entityStorageService->removeDownload($localPath);

        if ($pdftkPageExtractResult->isFailed()) {
            $this->logger->error('Failed to fetch PDF page', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'pageNr' => $pdftkPageExtractResult->pageNr,
                'sourcePdf' => $pdftkPageExtractResult->sourcePdf,
                'targetPath' => $pdftkPageExtractResult->targetPath,
                'errorOutput' => $pdftkPageExtractResult->errorMessage,
            ]);

            $this->localFilesystem->deleteDirectory($tempDir);

            return;
        }

        $this->entityStorageService->storePage(new \SplFileInfo($targetPath), $entity, $pageNr);

        $this->localFilesystem->deleteDirectory($tempDir);
    }
}
