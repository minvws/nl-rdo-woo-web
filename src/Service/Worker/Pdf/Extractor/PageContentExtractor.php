<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Domain\Ingest\Content\ContentExtractCollection;
use App\Domain\Ingest\Content\ContentExtractOptions;
use App\Domain\Ingest\Content\ContentExtractService;
use App\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Service\Stats\WorkerStatsService;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Extractor that will extract content from a single page from a given entity.
 */
readonly class PageContentExtractor
{
    public function __construct(
        private LoggerInterface $logger,
        private SubTypeIndexer $subTypeIndexer,
        private ContentExtractService $contentExtractService,
        private WorkerStatsService $statsService,
        private CacheItemPoolInterface $contentExtractCache,
    ) {
    }

    public function extract(PdfPageProcessingContext $context): void
    {
        $cacheItem = $this->getCacheItem($context->getEntity(), $context->getPageNumber());
        if (! $cacheItem->isHit()) {
            $cacheItem->set(
                $this->getExtractCollection($context)->getCombinedContent(),
            );
            $this->contentExtractCache->save($cacheItem);
        }

        /** @var string $combinedContent */
        $combinedContent = $cacheItem->get();

        $this->statsService->measure(
            'index.full.entity',
            fn () => $this->indexPage(
                $context->getEntity(),
                $context->getPageNumber(),
                $combinedContent,
            ),
        );
    }

    public function hasCache(EntityWithFileInfo $entity, int $pageNr): bool
    {
        return $this->getCacheItem($entity, $pageNr)->isHit();
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

    private function getExtractCollection(PdfPageProcessingContext $context): ContentExtractCollection
    {
        /** @var ContentExtractCollection */
        return $this->statsService->measure(
            'content.extract.entity',
            fn () => $this->contentExtractService->getExtracts(
                $context->getEntity(),
                ContentExtractOptions::create()
                    ->withAllExtractors()
                    ->withPageNumber($context->getPageNumber())
                    ->withLocalFile($context->getLocalPageDocument()),
            ),
        );
    }

    private function getCacheItem(EntityWithFileInfo $entity, int $pageNr): CacheItemInterface
    {
        return $this->contentExtractCache->getItem(
            sprintf(
                '%s-%s',
                $entity->getFileInfo()->getHash(),
                $pageNr,
            ),
        );
    }
}
