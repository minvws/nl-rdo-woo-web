<?php

declare(strict_types=1);

namespace Shared\Domain\Robots;

use Shared\ValueObject\Url;

final readonly class RobotsViewModel
{
    public function __construct(
        public ?Url $wooIndexSitemap,
        public Url $sitemap,
    ) {
    }

    public function hasWooIndexSitemap(): bool
    {
        return $this->wooIndexSitemap !== null;
    }
}
