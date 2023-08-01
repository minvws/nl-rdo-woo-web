<?php

declare(strict_types=1);

namespace App\Message;

class InitializeElasticRolloverMessage
{
    public function __construct(
        public readonly int $mappingVersion,
        public readonly string $indexName,
    ) {
    }
}
