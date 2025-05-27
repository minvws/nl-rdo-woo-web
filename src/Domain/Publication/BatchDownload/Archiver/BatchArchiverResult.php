<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload\Archiver;

final readonly class BatchArchiverResult
{
    public function __construct(
        public string $filename,
        public int $size,
        public int $fileCount,
    ) {
    }
}
