<?php

declare(strict_types=1);

namespace App\Service\Worker;

use App\Entity\Document;
use App\Service\Worker\Pdf\Extractor\DocumentContentExtractor;
use App\Service\Worker\Pdf\Extractor\PageContentExtractor;
use App\Service\Worker\Pdf\Extractor\PageExtractor;
use App\Service\Worker\Pdf\Extractor\ThumbnailExtractor;

class PdfProcessor
{
    public function __construct(
        private readonly ThumbnailExtractor $thumbnailExtractor,
        private readonly DocumentContentExtractor $docContentExtractor,
        private readonly PageContentExtractor $pageContentExtractor,
        private readonly PageExtractor $pageExtractor
    ) {
    }

    /**
     * Processes a single document page
     *   - extracts the page as single PDF from the document
     *   - generates and stores thumbnail from the single page
     *   - ingests the page content into the search index.
     */
    public function processDocumentPage(Document $document, int $pageNr, bool $forceRefresh): void
    {
        $this->pageExtractor->extract($document, $pageNr, $forceRefresh);

        $this->thumbnailExtractor->extract($document, $pageNr, $forceRefresh);

        $this->pageContentExtractor->extract($document, $pageNr, $forceRefresh);
    }

    /**
     * Processes a multi-page document
     *    - extracts the content of the PDF.
     */
    public function processDocument(Document $document, bool $forceRefresh): void
    {
        $this->docContentExtractor->extract($document, $forceRefresh);
    }
}
