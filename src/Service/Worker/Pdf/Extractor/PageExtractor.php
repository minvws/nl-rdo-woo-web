<?php

declare(strict_types=1);

namespace Shared\Service\Worker\Pdf\Extractor;

use Shared\Domain\Ingest\Process\PdfPage\PdfPageException;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkService;

readonly class PageExtractor
{
    public function __construct(
        protected PdftkService $pdftkService,
        protected EntityStorageService $entityStorageService,
    ) {
    }

    public function extractSinglePagePdf(PdfPageProcessingContext $context): void
    {
        $source = $this->entityStorageService->retrieveResourceEntity($context->getEntity());
        if ($source === null) {
            throw PdfPageException::forCannotRead($context->getEntity());
        }

        $pdftkPageExtractResult = $this->pdftkService->extractPage($source, $context->getPageNumber());

        if ($pdftkPageExtractResult->isFailed()) {
            throw PdfPageException::forCannotExtractPage($context, $pdftkPageExtractResult->errorMessage);
        }

        $context->setPageContents($pdftkPageExtractResult->pageContents);
    }
}
