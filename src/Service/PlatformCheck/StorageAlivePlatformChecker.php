<?php

declare(strict_types=1);

namespace Shared\Service\PlatformCheck;

use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\StorageAliveInterface;
use Shared\Service\Storage\ThumbnailStorageService;

readonly class StorageAlivePlatformChecker implements PlatformCheckerInterface
{
    public function __construct(
        private EntityStorageService $entityStoreService,
        private ThumbnailStorageService $thumbStoreService,
    ) {
    }

    /**
     * @return PlatformCheckResult[]
     */
    public function getResults(): array
    {
        return [
            $this->checkAlive('entity file store', $this->entityStoreService),
            $this->checkAlive('thumbnail file store', $this->thumbStoreService),
        ];
    }

    protected function checkAlive(string $name, StorageAliveInterface $storage): PlatformCheckResult
    {
        $description = 'Checking if ' . $name . ' is alive';

        if ($storage->isAlive()) {
            return PlatformCheckResult::success($description);
        }

        return PlatformCheckResult::error($description, 'not available or not writable');
    }
}
