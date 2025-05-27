<?php

declare(strict_types=1);

namespace App\Domain\WooIndex;

readonly class WooIndexNamer
{
    public function getStorageSubpath(WooIndexSitemap $wooIndexSitemap): string
    {
        return sprintf('%s/', $wooIndexSitemap->getId()->toRfc4122());
    }

    public function getSitemapName(int $sitemapNumber): string
    {
        return sprintf('sitemap-%05d.xml', $sitemapNumber);
    }

    public function getSitemapIndexName(): string
    {
        return 'sitemap-index.xml';
    }
}
