<?php

declare(strict_types=1);

namespace Shared\Domain\FileStorage\Checker;

use function array_key_exists;

class PathSet
{
    public int $totalSize = 0;
    public int $totalCount = 0;

    /**
     * @param array<string, string> $expectedPaths
     */
    public function __construct(
        public string $name,
        public FileStorageType $fileStorageType,
        private array $expectedPaths,
    ) {
    }

    public function matches(FileStorageType $type, string $path, int $size): bool
    {
        if ($type !== $this->fileStorageType || ! array_key_exists($path, $this->expectedPaths)) {
            return false;
        }

        unset($this->expectedPaths[$path]);

        $this->totalCount++;
        $this->totalSize += $size;

        return true;
    }

    /**
     * @return array<string, string>
     */
    public function getRemainingPaths(): array
    {
        return $this->expectedPaths;
    }
}
