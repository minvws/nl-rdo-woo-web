<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Domain\Ingest\Process\PdfPage\PdfPageException;
use App\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use App\Service\Storage\ThumbnailStorageService;
use App\Service\Worker\Pdf\Tools\Pdftoppm\PdftoppmService;
use Symfony\Component\HttpFoundation\File\File;

readonly class ThumbnailExtractor
{
    public function __construct(
        private ThumbnailStorageService $thumbnailStorage,
        private PdftoppmService $pdfToPpmService,
        private int $thumbnailLimit,
    ) {
    }

    public function extractSinglePagePdfThumbnail(PdfPageProcessingContext $context): void
    {
        if ($context->getPageNumber() > $this->thumbnailLimit) {
            return;
        }

        $targetPath = $context->getWorkDirPath() . '/thumb';  // pdftoppm will add the extension

        $pdfToPpmResult = $this->pdfToPpmService->createThumbnail($context->getLocalPageDocument(), $targetPath);
        if ($pdfToPpmResult->isFailed()) {
            throw PdfPageException::forCannotCreateThumbnail($context, $pdfToPpmResult->errorMessage);
        }

        $this->thumbnailStorage->store(
            $context->getEntity(),
            new File($targetPath . '.png'),
            $context->getPageNumber(),
        );
    }
}
