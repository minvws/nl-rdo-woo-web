<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\PdfPage;

use App\Domain\Ingest\Content\ContentExtractCache;
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
        private ContentExtractCache $contentExtractCache,
        private PageExtractor $pageExtractor,
    ) {
    }

    public function processPage(EntityWithFileInfo $entity, int $pageNr): void
    {
        Assert::true($entity->getFileInfo()->isPaginatable(), 'Entity is not paginatable');

        $processingContext = $this->contextFactory->createContext($entity, $pageNr);
        if ($processingContext === null) {
            return;
        }

        try {
            $needsThumbGeneration = $this->thumbnailExtractor->needsThumbGeneration($processingContext);
            $contentCacheMissing = ! $this->contentExtractCache->hasCache($entity, $pageNr);
            if ($needsThumbGeneration || $contentCacheMissing) {
                // Only download the file when actual processing is needed
                $this->pageExtractor->extractSinglePagePdf($processingContext);
            }

            if ($needsThumbGeneration) {
                $this->thumbnailExtractor->extractSinglePagePdfThumbnail($processingContext);
            }

            // Always executed because it also indexes content into ES, but in most cases this can be done from cache
            $this->pageContentExtractor->extract($processingContext);
        } finally {
            $this->contextFactory->teardown($processingContext);
        }
    }
}
