<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content;

use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;

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
