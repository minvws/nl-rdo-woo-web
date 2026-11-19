<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Rollover;

class InitiateElasticRolloverCommand
{
    public function __construct(
        public readonly string $indexName,
    ) {
    }
}
