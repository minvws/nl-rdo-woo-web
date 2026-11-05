<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Robots;

use Mockery;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Robots\RobotsViewFactory;
use Shared\Domain\WooIndex\WooIndexSitemapService;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\Url;

final class RobotsViewFactoryTest extends UnitTestCase
{
    public function testMake(): void
    {
        $sitemapIndex = Url::create($this->getFaker()->url());
        $sitemap = $this->getFaker()->url();

        $wooIndexSitemapService = Mockery::mock(WooIndexSitemapService::class);
        $wooIndexSitemapService->expects('getCurrentSitemapIndexUrl')
            ->andReturn($sitemapIndex);

        $publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $publicUrlGenerator->expects('buildUrlFromPath')
            ->with('sitemap.xml')
            ->andReturn(Url::create($sitemap));

        $robotsViewFactory = new RobotsViewFactory($wooIndexSitemapService, $publicUrlGenerator);
        $robotsViewModel = $robotsViewFactory->make();

        $this->assertSame($sitemapIndex, $robotsViewModel->wooIndexSitemap);
        $this->assertSame($sitemap, $robotsViewModel->sitemap->toString());
        $this->assertTrue($robotsViewModel->hasWooIndexSitemap());
    }
}
