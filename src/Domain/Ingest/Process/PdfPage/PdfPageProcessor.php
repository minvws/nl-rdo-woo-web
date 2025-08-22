<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\PdfPage;

use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Worker\Pdf\Extractor\PageContentExtractor;
use App\Service\Worker\Pdf\Extractor\PageExtractor;
use App\Service\Worker\Pdf\Extractor\ThumbnailExtractor;
use Webmozart\Assert\Assert;

readonly class PdfPageProcessor
{
    public function __construct(
        private PdfPageProcessingContextFactory $contextFactory,
        private ThumbnailExtractor $thumbnailExtractor,
        private PageContentExtractor $pageContentExtractor,
        private PageExtractor $pageExtractor,
    ) {
    }

    public function processPage(EntityWithFileInfo $entity, int $pageNr, bool $forceRefresh): void
    {
        Assert::true($entity->getFileInfo()->isPaginatable(), 'Entity is not paginatable');

        $processingContext = $this->contextFactory->createContext($entity, $pageNr);
        if ($processingContext === null) {
            return;
        }

        try {
            $this->pageExtractor->extractSinglePagePdf($processingContext);
            $this->thumbnailExtractor->extractSinglePagePdfThumbnail($processingContext);
            $this->pageContentExtractor->extract($processingContext, $forceRefresh);
        } finally {
            $this->contextFactory->teardown($processingContext);
        }
    }
}
