<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Robots;

use App\Domain\Robots\RobotsViewFactory;
use App\Domain\WooIndex\WooIndexFileManager;
use App\Domain\WooIndex\WooIndexNamer;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class RobotsViewFactoryTest extends UnitTestCase
{
    private WooIndexFileManager&MockInterface $wooIndexFileManager;
    private WooIndexNamer&MockInterface $wooIndexNamer;
    private RobotsViewFactory $robotsViewFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wooIndexFileManager = \Mockery::mock(WooIndexFileManager::class);
        $this->wooIndexNamer = \Mockery::mock(WooIndexNamer::class);

        $this->robotsViewFactory = new RobotsViewFactory(
            $this->wooIndexFileManager,
            $this->wooIndexNamer,
        );
    }

    public function testMake(): void
    {
        $this->wooIndexFileManager
            ->shouldReceive('getLastPublished')
            ->andReturn($path = '/var/www/html/public/sitemap/woo-index/latest_woo_index');

        $this->wooIndexNamer
            ->shouldReceive('getSitemapIndexName')
            ->andReturn($sitemapName = 'sitemapindex.xml');

        $this->wooIndexNamer
            ->shouldReceive('joinPaths')
            ->with($path, $sitemapName)
            ->andReturn($fullPath = sprintf('%s/%s', $path, $sitemapName));

        $result = $this->robotsViewFactory->make();

        $this->assertSame($fullPath, $result->wooIndexSitemap);
        $this->assertTrue($result->hasWooIndexSitemap());
    }

    public function testMakeWhenNoWooIndexIsPublished(): void
    {
        $this->wooIndexFileManager
            ->shouldReceive('getLastPublished')
            ->andReturnNull();

        $result = $this->robotsViewFactory->make();

        $this->assertNull($result->wooIndexSitemap);
        $this->assertFalse($result->hasWooIndexSitemap());
    }
}
