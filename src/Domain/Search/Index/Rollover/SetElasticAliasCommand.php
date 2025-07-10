<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Rollover;

class SetElasticAliasCommand
{
    public function __construct(
        public readonly string $indexName,
        public readonly string $aliasName,
    ) {
    }
}
