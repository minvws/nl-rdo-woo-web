<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content\Extractor\Tika;

use App\Domain\Ingest\Content\ContentExtractLogContext;
use App\Domain\Ingest\Content\Extractor\ContentExtractorInterface;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Ingest\Content\FileReferenceInterface;
use App\Domain\Publication\EntityWithFileInfo;

readonly class TikaExtractor implements ContentExtractorInterface
{
    public function __construct(
        private TikaService $tikaService,
    ) {
    }

    public function getContent(EntityWithFileInfo $entity, FileReferenceInterface $fileReference): string
    {
        $tikaData = $this->tikaService->extract(
            sourcePath: $fileReference->getPath(),
            contentType: $entity->getFileInfo()->getNormalizedMimeType(),
            logContext: ContentExtractLogContext::forEntity($entity),
        );

        return trim($tikaData['X-TIKA:content'] ?? '');
    }

    public function supports(EntityWithFileInfo $entity): bool
    {
        return ! empty($entity->getFileInfo()->getNormalizedMimeType());
    }

    public function getKey(): ContentExtractorKey
    {
        return ContentExtractorKey::TIKA;
    }
}
