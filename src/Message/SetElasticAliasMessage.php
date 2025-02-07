<?php

declare(strict_types=1);

namespace App\Message;

class SetElasticAliasMessage
{
    public function __construct(
        public readonly string $indexName,
        public readonly string $aliasName,
    ) {
    }
}
