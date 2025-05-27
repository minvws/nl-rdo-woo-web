<?php

declare(strict_types=1);

namespace App\Domain\FileStorage\Checker;

readonly class FileStorageCheckResult
{
    /**
     * @param array<array-key, PathSet> $pathSets
     */
    public function __construct(
        public OrphanedPaths $orphanedPaths,
        public array $pathSets,
    ) {
    }
}
