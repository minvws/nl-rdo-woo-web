<?php

declare(strict_types=1);

namespace Shared\Domain\Robots;

final readonly class RobotsViewModel
{
    public function __construct(
        public ?string $wooIndexSitemap,
        public string $publicBaseUrl,
    ) {
    }

    public function hasWooIndexSitemap(): bool
    {
        return $this->wooIndexSitemap !== null;
    }
}
