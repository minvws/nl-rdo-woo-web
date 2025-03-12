<?php

declare(strict_types=1);

namespace App\Domain\WooIndex;

use App\Domain\WooIndex\Producer\InvalidChunkSizeException;

final readonly class WooIndexRunOptions
{
    public const DEFAULT_CHUNK_SIZE = self::MAX_CHUNK_SIZE;
    public const MAX_CHUNK_SIZE = 50_000;

    public function __construct(
        public int $chunkSize = self::DEFAULT_CHUNK_SIZE,
        public ?string $pathSuffix = null,
    ) {
        if ($chunkSize < 1 || $chunkSize > self::MAX_CHUNK_SIZE) {
            throw InvalidChunkSizeException::create();
        }
    }
}
