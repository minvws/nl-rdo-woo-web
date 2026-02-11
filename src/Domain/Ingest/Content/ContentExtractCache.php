<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Shared\Domain\Publication\EntityWithFileInfo;

use function sprintf;

readonly class ContentExtractCache
{
    public function __construct(
        private ContentExtractService $contentExtractService,
        private CacheItemPoolInterface $contentExtractCache,
    ) {
    }

    public function getCombinedExtracts(
        EntityWithFileInfo $entity,
        ContentExtractOptions $options,
    ): string {
        $cacheItem = $this->getCacheItem($entity, $options->getPageNumber());
        if (! $cacheItem->isHit()) {
            $cacheItem->set(
                $this->contentExtractService->getExtracts($entity, $options)->getCombinedContent(),
            );
            $this->contentExtractCache->save($cacheItem);
        }

        /** @var string */
        return $cacheItem->get();
    }

    public function hasCache(EntityWithFileInfo $entity, int $pageNr): bool
    {
        return $this->getCacheItem($entity, $pageNr)->isHit();
    }

    private function getCacheItem(EntityWithFileInfo $entity, ?int $pageNr): CacheItemInterface
    {
        return $this->contentExtractCache->getItem(
            sprintf(
                '%s-%s',
                $entity->getFileInfo()->getHash(),
                $pageNr ?? 0,
            ),
        );
    }
}
