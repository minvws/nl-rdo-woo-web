<?php

declare(strict_types=1);

namespace App\Service\Elastic\Model;

class Index
{
    /**
     * @param string[] $aliases
     */
    public function __construct(
        public readonly string $name,
        public readonly string $health,
        public readonly string $status,
        public readonly string $docsCount,
        public readonly string $storeSize,
        public readonly array $aliases = [],
    ) {
    }
}
