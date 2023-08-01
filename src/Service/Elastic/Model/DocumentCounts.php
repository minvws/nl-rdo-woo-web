<?php

declare(strict_types=1);

namespace App\Service\Elastic\Model;

class DocumentCounts
{
    public function __construct(
        public readonly int $documentCount,
        public readonly int $totalPageCount,
    ) {
    }
}
