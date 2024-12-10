<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content;

use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Publication\EntityWithFileInfo;
use Webmozart\Assert\Assert;

class ContentExtractCacheKeyGenerator
{
    public function generate(
        ContentExtractorKey $extractorKey,
        EntityWithFileInfo $entity,
        ContentExtractOptions $options,
    ): string {
        $hash = $entity->getFileInfo()->getHash();
        Assert::notNull(
            $hash,
            'Cannot generate cache key for entity without hash in FileInfo',
        );

        return implode(
            '-',
            [
                $extractorKey->value,
                $entity->getFileCacheKey(),
                $entity->getId()->toRfc4122(),
                $options->getPageNumber() ?? 0,
                $hash,
            ]
        );
    }
}
