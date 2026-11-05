<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer;

use Shared\ValueObject\Url;

final readonly class UrlReference
{
    public function __construct(
        public Url $resource,
        public string $officieleTitel,
    ) {
    }
}
