<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content\Extractor;

use App\Domain\Ingest\Content\LazyFileReference;
use App\Domain\Publication\FileInfo;

interface ContentExtractorInterface
{
    public function getContent(FileInfo $fileInfo, LazyFileReference $fileReference): string;

    public function supports(FileInfo $fileInfo): bool;

    public function getKey(): ContentExtractorKey;
}
