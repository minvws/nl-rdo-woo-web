<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\WooIndex;

use App\Domain\WooIndex\Builder\DiWooXMLWriter;
use App\Domain\WooIndex\Builder\SitemapBuilder;
use App\Domain\WooIndex\Builder\SitemapIndexBuilder;
use App\Domain\WooIndex\StreamHelper;
use App\Domain\WooIndex\WooIndex;
use App\Domain\WooIndex\WooIndexNamer;
use App\Domain\WooIndex\WooIndexRunOptions;
use App\Domain\WooIndex\WooIndexSitemap;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Story\WooIndexAnnualReportStory;
use App\Tests\Story\WooIndexCovenantStory;
use App\Tests\Story\WooIndexWooDecisionStory;
use League\Flysystem\FilesystemOperator;
use Mockery\MockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Attribute\WithStory;

final class WooIndexTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private StreamHelper&MockInterface $streamHelper;

    private FilesystemOperator $wooIndexStorage;

    private WooIndexNamer $wooIndexNamer;

    private WooIndex $wooIndex;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->streamHelper = \Mockery::mock(StreamHelper::class)->makePartial();
        self::getContainer()->set(StreamHelper::class, $this->streamHelper);

        $this->wooIndexStorage = self::getContainer()->get('woo_index.storage');

        $this->wooIndexNamer = self::getContainer()->get(WooIndexNamer::class);

        /** @var SitemapIndexBuilder $sitemapIndexBuilder */
        $sitemapIndexBuilder = self::getContainer()->get(SitemapIndexBuilder::class);
        $sitemapIndexBuilder->setXMLWriterConfigurator(function (DiWooXMLWriter $writer) {
            $writer->setIndent(true);
        });

        /** @var SitemapBuilder $sitemapIndexBuilder */
        $sitemapBuilder = self::getContainer()->get(SitemapBuilder::class);
        $sitemapBuilder->setXMLWriterConfigurator(function (DiWooXMLWriter $writer) {
            $writer->setIndent(true);
        });

        $this->wooIndex = self::getContainer()->get(WooIndex::class);
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    #[WithStory(WooIndexAnnualReportStory::class)]
    #[WithStory(WooIndexCovenantStory::class)]
    public function testCreate(): void
    {
        $this->setTestNow('2025-01-01 00:00:00');

        $wooIndexSitemap = $this->getWooIndexSitemap(Uuid::fromRfc4122('1efe8c60-9d44-6c08-8984-db09e5d32982'));
        $subPath = $this->wooIndexNamer->getStorageSubpath($wooIndexSitemap);
        $options = new WooIndexRunOptions(chunkSize: 2);
        $this->wooIndex->create($wooIndexSitemap, $options);

        $sitemapIndexPath = $subPath . $this->wooIndexNamer->getSitemapIndexName();
        $this->assertTrue($this->wooIndexStorage->has($sitemapIndexPath));
        $this->assertMatchesTextSnapshot($this->wooIndexStorage->read($sitemapIndexPath));

        foreach (range(1, 18) as $sitemapNumber) {
            $sitemapPath = $subPath . $this->wooIndexNamer->getSitemapName($sitemapNumber);
            $this->assertTrue($this->wooIndexStorage->has($sitemapPath), sprintf('Sitemap %s exists', $sitemapNumber));
            $this->assertMatchesTextSnapshot($this->wooIndexStorage->read($sitemapPath));
        }

        $sitemapPath = $subPath . $this->wooIndexNamer->getSitemapName(19);
        $this->assertFalse($this->wooIndexStorage->has($sitemapPath), 'Sitemap 19 does not exist');
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    public function testCreateWithOneSitemapReachingFileSize(): void
    {
        $this->setTestNow('2025-01-01 00:00:00');

        // On the 7th iteration we will reach the file size limit, the rest will return 0 bytes
        $this->streamHelper->shouldReceive('size')->andReturnValues([0, 0, 0, 0, 0, 0, 49 * 1024 * 1024 + 1, 0]);

        $wooIndexSitemap = $this->getWooIndexSitemap(Uuid::fromRfc4122('1efe8c60-9d44-6c08-8984-db09e5d32982'));
        $subPath = $this->wooIndexNamer->getStorageSubpath($wooIndexSitemap);
        $options = new WooIndexRunOptions(chunkSize: 4);
        $this->wooIndex->create($wooIndexSitemap, $options);

        $sitemapIndexPath = $subPath . $this->wooIndexNamer->getSitemapIndexName();
        $this->assertTrue($this->wooIndexStorage->has($sitemapIndexPath), 'SitemapIndex exists');
        $this->assertMatchesTextSnapshot($this->wooIndexStorage->read($sitemapIndexPath));

        foreach (range(1, 8) as $sitemapNumber) {
            $sitemapPath = $subPath . $this->wooIndexNamer->getSitemapName($sitemapNumber);
            $this->assertTrue($this->wooIndexStorage->has($sitemapPath), sprintf('Sitemap %s exists', $sitemapNumber));
            $this->assertMatchesTextSnapshot($this->wooIndexStorage->read($sitemapPath));
        }

        $sitemapPath = $subPath . $this->wooIndexNamer->getSitemapName(9);
        $this->assertFalse($this->wooIndexStorage->has($sitemapPath), 'Sitemap 9 does not exist');
    }

    private function getWooIndexSitemap(Uuid $uuid): WooIndexSitemap
    {
        $wooIndexSitemap = new WooIndexSitemap();

        $reflection = new \ReflectionClass($wooIndexSitemap);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($wooIndexSitemap, $uuid);

        return $wooIndexSitemap;
    }
}
