<?php

declare(strict_types=1);

namespace App\Domain\Robots;

use App\Domain\WooIndex\WooIndexSitemapService;

final readonly class RobotsViewFactory
{
    public function __construct(
        private WooIndexSitemapService $wooIndexSitemapService,
        private string $publicBaseUrl,
    ) {
    }

    public function make(): RobotsViewModel
    {
        return new RobotsViewModel(
            wooIndexSitemap: $this->wooIndexSitemapService->getCurrentSitemapIndexUrl(),
            publicBaseUrl: $this->publicBaseUrl,
        );
    }
}
