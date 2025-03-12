<?php

declare(strict_types=1);

namespace App\Domain\Robots;

final readonly class RobotsViewModel
{
    public function __construct(
        public ?string $wooIndexSitemap,
    ) {
    }

    public function hasWooIndexSitemap(): bool
    {
        return $this->wooIndexSitemap !== null;
    }
}
