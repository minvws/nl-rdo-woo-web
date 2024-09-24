<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content\Extractor\Tika;

use App\Domain\Ingest\Content\Extractor\ContentExtractorInterface;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Ingest\Content\LazyFileReference;
use App\Entity\FileInfo;

readonly class TikaExtractor implements ContentExtractorInterface
{
    public function __construct(
        private TikaService $tikaService,
    ) {
    }

    public function getContent(FileInfo $fileInfo, LazyFileReference $fileReference): string
    {
        $tikaData = $this->tikaService->extract(
            $fileReference->getPath(),
            $fileInfo->getNormalizedMimeType(),
        );

        return trim($tikaData['X-TIKA:content'] ?? '');
    }

    public function supports(FileInfo $fileInfo): bool
    {
        return ! empty($fileInfo->getNormalizedMimeType());
    }

    public function getKey(): ContentExtractorKey
    {
        return ContentExtractorKey::TIKA;
    }
}
