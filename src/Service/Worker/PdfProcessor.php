<?php

declare(strict_types=1);

namespace App\Service\Worker;

use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Worker\Pdf\Extractor\EntityMetaDataExtractor;
use App\Service\Worker\Pdf\Extractor\PageContentExtractor;
use App\Service\Worker\Pdf\Extractor\PageExtractor;
use App\Service\Worker\Pdf\Extractor\ThumbnailExtractor;

class PdfProcessor
{
    public function __construct(
        private readonly ThumbnailExtractor $thumbnailExtractor,
        private readonly EntityMetaDataExtractor $docContentExtractor,
        private readonly PageContentExtractor $pageContentExtractor,
        private readonly PageExtractor $pageExtractor,
    ) {
    }

    /**
     * Processes a single entity page
     *   - extracts the page as single PDF from the entity
     *   - generates and stores thumbnail from the single page
     *   - ingests the page content into the search index.
     */
    public function processEntityPage(EntityWithFileInfo $entity, int $pageNr, bool $forceRefresh): void
    {
        $this->pageExtractor->extract($entity, $pageNr, $forceRefresh);

        $this->thumbnailExtractor->extract($entity, $pageNr, $forceRefresh);

        $this->pageContentExtractor->extract($entity, $pageNr, $forceRefresh);
    }

    /**
     * Processes a multi-page document
     *    - extracts the content of the PDF.
     */
    public function processEntity(EntityWithFileInfo $entity, bool $forceRefresh): void
    {
        $this->docContentExtractor->extract($entity, $forceRefresh);
    }
}
