<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content\Event;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler]
readonly class ContentExtractCacheInvalidator
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(EntityFileUpdateEvent $event): void
    {
        $this->logger->info('Invalidating content extract cache for entity with id ' . $event->entityId->toRfc4122());

        $this->cache->invalidateTags([
            $event->entityId->toRfc4122(),
        ]);
    }
}
