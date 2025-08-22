<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Stats\WorkerStatsService;
use App\Service\Storage\EntityStorageService;
use App\Service\Worker\Pdf\Tools\Pdftk\PdftkPageCountResult;
use App\Service\Worker\Pdf\Tools\Pdftk\PdftkService;
use Psr\Log\LoggerInterface;

/**
 * Extractor that will extract the page count of a PDF entity.
 *
 * @implements OutputExtractorInterface<PdftkPageCountResult>
 */
class PagecountExtractor implements EntityExtractorInterface, OutputExtractorInterface
{
    protected ?PdftkPageCountResult $output = null;

    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly PdftkService $pdftkService,
        protected readonly EntityStorageService $entityStorageService,
        protected readonly WorkerStatsService $statsService,
    ) {
    }

    public function extract(EntityWithFileInfo $entity, bool $forceRefresh): void
    {
        // TODO: Cache was removed for #2142, to be improved and restored in #2144
        unset($forceRefresh);

        $this->output = $this->extractPageCountFromPdf($entity);
    }

    protected function extractPageCountFromPdf(EntityWithFileInfo $entity): ?PdftkPageCountResult
    {
        /** @var string|false $localPdfPath */
        $localPdfPath = $this->statsService->measure(
            'download.entity',
            fn (): string|false => $this->entityStorageService->downloadEntity($entity),
        );
        if ($localPdfPath === false) {
            $this->logger->error('Failed to download entity for page count extraction', [
                'id' => $entity->getId(),
                'class' => $entity::class,
            ]);

            return null;
        }

        /** @var PdftkPageCountResult $pdftkPageCountResult */
        $pdftkPageCountResult = $this->statsService->measure(
            'pdftk.extractNumberOfPages',
            fn (): PdftkPageCountResult => $this->pdftkService->extractNumberOfPages($localPdfPath),
        );

        $this->entityStorageService->removeDownload($localPdfPath);

        if ($pdftkPageCountResult->isFailed()) {
            $this->logger->error('Failed to get number of pages', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'sourcePdf' => $pdftkPageCountResult->sourcePdf,
                'errorOutput' => $pdftkPageCountResult->errorMessage,
            ]);
        }

        return $pdftkPageCountResult;
    }

    public function getOutput(): ?PdftkPageCountResult
    {
        return $this->output;
    }
}
