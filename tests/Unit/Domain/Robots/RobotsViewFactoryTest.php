<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Robots;

use App\Domain\Robots\RobotsViewFactory;
use App\Domain\WooIndex\WooIndexSitemapService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class RobotsViewFactoryTest extends UnitTestCase
{
    private RobotsViewFactory $robotsViewFactory;

    private WooIndexSitemapService&MockInterface $wooIndexSitemapService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wooIndexSitemapService = \Mockery::mock(WooIndexSitemapService::class);

        $this->robotsViewFactory = new RobotsViewFactory($this->wooIndexSitemapService);
    }

    public function testMake(): void
    {
        $this->wooIndexSitemapService
            ->shouldReceive('getCurrentSitemapIndexUrl')
            ->andReturn($sitemapIndex = 'https://example.com/sitemapindex.xml');

        $result = $this->robotsViewFactory->make();

        $this->assertSame($sitemapIndex, $result->wooIndexSitemap);
        $this->assertTrue($result->hasWooIndexSitemap());
    }
}
