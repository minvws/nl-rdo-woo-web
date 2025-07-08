<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Domain\Ingest\Process\PdfPage\PdfPageException;
use App\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use App\Service\Stats\WorkerStatsService;
use App\Service\Worker\Pdf\Tools\Pdftk\PdftkPageExtractResult;
use App\Service\Worker\Pdf\Tools\Pdftk\PdftkService;

readonly class PageExtractor
{
    public function __construct(
        protected PdftkService $pdftkService,
        protected WorkerStatsService $statsService,
    ) {
    }

    public function extractSinglePagePdf(PdfPageProcessingContext $context): void
    {
        $targetPath = $context->getWorkDirPath() . '/page.pdf';

        /** @var PdftkPageExtractResult $pdftkPageExtractResult */
        $pdftkPageExtractResult = $this->statsService->measure(
            'pdftk.extractPage',
            fn (): PdftkPageExtractResult => $this->pdftkService->extractPage(
                $context->getLocalDocument(),
                $context->getPageNumber(),
                $targetPath,
            ),
        );

        if ($pdftkPageExtractResult->isFailed()) {
            throw PdfPageException::forCannotExtractPage($context, $pdftkPageExtractResult->errorMessage);
        }

        $context->setLocalPageDocument($targetPath);
    }
}
