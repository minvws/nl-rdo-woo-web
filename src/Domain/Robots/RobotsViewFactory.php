<?php

declare(strict_types=1);

namespace App\Domain\Robots;

use App\Domain\WooIndex\WooIndexFileManager;
use App\Domain\WooIndex\WooIndexNamer;

final readonly class RobotsViewFactory
{
    public function __construct(
        private WooIndexFileManager $wooIndexFileManager,
        private WooIndexNamer $wooIndexNamer,
    ) {
    }

    public function make(): RobotsViewModel
    {
        return new RobotsViewModel(
            wooIndexSitemap: $this->getWooIndexSitemap(),
        );
    }

    private function getWooIndexSitemap(): ?string
    {
        $wooIndexPath = $this->wooIndexFileManager->getLastPublished();
        if ($wooIndexPath === null) {
            return null;
        }

        return $this->wooIndexNamer->joinPaths(
            $wooIndexPath,
            $this->wooIndexNamer->getSitemapIndexName(),
        );
    }
}
