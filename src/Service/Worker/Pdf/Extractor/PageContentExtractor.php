<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Domain\Ingest\Content\ContentExtractCache;
use App\Domain\Ingest\Content\ContentExtractOptions;
use App\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Service\Stats\WorkerStatsService;
use Psr\Log\LoggerInterface;

/**
 * Extractor that will extract content from a single page from a given entity and index it into ES.
 */
readonly class PageContentExtractor
{
    public function __construct(
        private LoggerInterface $logger,
        private SubTypeIndexer $subTypeIndexer,
        private ContentExtractCache $contentExtractCache,
        private WorkerStatsService $statsService,
    ) {
    }

    public function extract(PdfPageProcessingContext $context): void
    {
        $extracts = $this->contentExtractCache->getCombinedExtracts(
            $context->getEntity(),
            ContentExtractOptions::create()
                ->withAllExtractors()
                ->withPageNumber($context->getPageNumber())
                ->withLocalFile($context->getOptionalLocalPageDocument())
        );

        $this->statsService->measure(
            'index.full.entity',
            fn () => $this->indexPage(
                $context->getEntity(),
                $context->getPageNumber(),
                $extracts,
            ),
        );
    }

    private function indexPage(EntityWithFileInfo $entity, int $pageNr, string $content): void
    {
        try {
            $this->subTypeIndexer->updatePage($entity, $pageNr, $content);
        } catch (\Exception $e) {
            $this->logger->error('Failed to index page', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'pageNr' => $pageNr,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
