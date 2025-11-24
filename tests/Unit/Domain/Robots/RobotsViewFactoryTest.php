<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Robots;

use Shared\Domain\Robots\RobotsViewFactory;
use Shared\Domain\WooIndex\WooIndexSitemapService;
use Shared\Tests\Unit\UnitTestCase;

final class RobotsViewFactoryTest extends UnitTestCase
{
    public function testMake(): void
    {
        $sitemapIndex = 'https://example.com/sitemapindex.xml';

        $wooIndexSitemapService = \Mockery::mock(WooIndexSitemapService::class);
        $wooIndexSitemapService->expects('getCurrentSitemapIndexUrl')
            ->andReturn($sitemapIndex);

        $robotsViewFactory = new RobotsViewFactory($wooIndexSitemapService, 'http://localhost');
        $robotsViewModel = $robotsViewFactory->make();

        $this->assertSame($sitemapIndex, $robotsViewModel->wooIndexSitemap);
        $this->assertTrue($robotsViewModel->hasWooIndexSitemap());
    }
}
