<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\ElasticIndex;

readonly class ElasticIndexDetails
{
    /**
     * @param string[] $aliases
     */
    public function __construct(
        public string $name,
        public string $health,
        public string $status,
        public string $docsCount,
        public string $storeSize,
        public string $mappingVersion,
        public array $aliases = [],
    ) {
    }
}
