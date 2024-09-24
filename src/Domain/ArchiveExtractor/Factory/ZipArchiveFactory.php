<?php

declare(strict_types=1);

namespace App\Domain\ArchiveExtractor\Factory;

readonly class ZipArchiveFactory
{
    public function create(): \ZipArchive
    {
        return new \ZipArchive();
    }
}
