<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content\Extractor;

use App\Domain\Ingest\Content\FileReferenceInterface;
use App\Domain\Publication\FileInfo;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.ingest.content_extractor')]
interface ContentExtractorInterface
{
    public function getContent(FileInfo $fileInfo, FileReferenceInterface $fileReference): string;

    public function supports(FileInfo $fileInfo): bool;

    public function getKey(): ContentExtractorKey;
}
