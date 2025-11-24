<?php

declare(strict_types=1);

namespace Shared\Domain\ArchiveExtractor\Factory;

use Archive7z\Archive7z;

readonly class SevenZipArchiveFactory
{
    public function create(string $filename, ?float $timeout = 60.0): Archive7z
    {
        return new Archive7z($filename, timeout: $timeout);
    }
}
