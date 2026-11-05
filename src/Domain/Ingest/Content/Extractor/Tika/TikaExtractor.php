<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content\Extractor\Tika;

use GuzzleHttp\Psr7\Utils;
use Shared\Domain\Ingest\Content\ContentExtractLogContext;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorInterface;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use Shared\Domain\Ingest\Content\FileReferenceInterface;
use Shared\Domain\Publication\EntityWithFileInfo;

use function array_key_exists;
use function trim;

readonly class TikaExtractor implements ContentExtractorInterface
{
    public function __construct(
        private TikaService $tikaService,
    ) {
    }

    public function getContent(EntityWithFileInfo $entity, FileReferenceInterface $fileReference): string
    {
        $tikaData = $this->tikaService->extract(
            source: Utils::streamFor($fileReference->getStream()),
            contentType: $entity->getFileInfo()->getNormalizedMimeType(),
            logContext: ContentExtractLogContext::forEntity($entity),
        );

        if (! array_key_exists('X-TIKA:content', $tikaData)) {
            return '';
        }

        return trim($tikaData['X-TIKA:content']);
    }

    public function supports(EntityWithFileInfo $entity): bool
    {
        return $entity->getFileInfo()->getNormalizedMimeType() !== '';
    }

    public function getKey(): ContentExtractorKey
    {
        return ContentExtractorKey::TIKA;
    }
}
