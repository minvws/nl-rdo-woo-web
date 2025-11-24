<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload;

readonly class DownloadDetails
{
    public function __construct(
        public string $name,
        public int $documentCount,
        public int $totalDocumentSize,
    ) {
    }
}
