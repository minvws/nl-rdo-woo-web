<?php

declare(strict_types=1);

namespace PublicationApi\Api\Prefix;

use Symfony\Component\Uid\Uuid;

final class PrefixResponseDto
{
    final public function __construct(
        public Uuid $id,
        public string $prefix,
    ) {
    }
}
