<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content\Extractor;

use App\Domain\Ingest\Content\FileReferenceInterface;
use App\Domain\Publication\EntityWithFileInfo;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.ingest.content_extractor')]
interface ContentExtractorInterface
{
    public function getContent(EntityWithFileInfo $entity, FileReferenceInterface $fileReference): string;

    public function supports(EntityWithFileInfo $entity): bool;

    public function getKey(): ContentExtractorKey;
}
