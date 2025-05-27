<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer;

final readonly class UrlReference
{
    public function __construct(
        public string $resource,
        public string $officieleTitel,
    ) {
    }
}
