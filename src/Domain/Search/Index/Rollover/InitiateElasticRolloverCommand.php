<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Rollover;

class InitiateElasticRolloverCommand
{
    public function __construct(
        public readonly int $mappingVersion,
        public readonly string $indexName,
    ) {
    }
}
