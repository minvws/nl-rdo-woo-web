<?php

declare(strict_types=1);

namespace Shared\Domain\FileStorage\Checker;

use function array_key_exists;

class OrphanedPaths
{
    /**
     * @var array<string, list<string>>
     */
    public array $paths = [];
    public int $totalCount = 0;
    public int $totalSize = 0;
    public string $name = 'Orphaned';

    public function add(FileStorageType $fileStorageType, string $path, int $size): void
    {
        if (! array_key_exists($fileStorageType->value, $this->paths)) {
            $this->paths[$fileStorageType->value] = [];
        }

        $this->paths[$fileStorageType->value][] = $path;

        $this->totalCount++;
        $this->totalSize += $size;
    }
}
