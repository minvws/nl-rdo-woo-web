<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content;

use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;

readonly class ContentExtract
{
    public \DateTimeImmutable $date;

    public function __construct(
        public ContentExtractorKey $key,
        public string $content,
    ) {
        $this->date = new \DateTimeImmutable();
    }
}
