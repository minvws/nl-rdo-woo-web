<?php

declare(strict_types=1);

namespace App\Domain\WooIndex;

use App\Domain\WooIndex\Producer\InvalidChunkSizeException;

final readonly class WooIndexRunOptions
{
    public const int DEFAULT_CHUNK_SIZE = self::MAX_CHUNK_SIZE;
    public const int MAX_CHUNK_SIZE = 50_000;

    public function __construct(
        public int $chunkSize = self::DEFAULT_CHUNK_SIZE,
    ) {
        if ($chunkSize < 1 || $chunkSize > self::MAX_CHUNK_SIZE) {
            throw InvalidChunkSizeException::create();
        }
    }
}
