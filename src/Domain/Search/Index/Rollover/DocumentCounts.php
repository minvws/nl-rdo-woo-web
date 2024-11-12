<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Rollover;

readonly class DocumentCounts
{
    public function __construct(
        public int $documentCount,
        public int $totalPageCount,
    ) {
    }
}
