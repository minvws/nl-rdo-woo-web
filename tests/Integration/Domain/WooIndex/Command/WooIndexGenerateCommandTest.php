<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\WooIndex\Command;

use League\Flysystem\FilesystemOperator;
use Shared\Domain\WooIndex\WooIndexNamer;
use Shared\Domain\WooIndex\WooIndexSitemapRepository;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\Tests\Story\WooIndexWooDecisionStory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;
use Zenstruck\Foundry\Attribute\WithStory;

final class WooIndexGenerateCommandTest extends SharedWebTestCase
{
    private FilesystemOperator $wooIndexStorage;
    private WooIndexSitemapRepository $wooIndexSitemapRepository;
    private WooIndexNamer $wooIndexNamer;
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wooIndexStorage = self::getContainer()->get('woo_index.storage');
        $this->wooIndexSitemapRepository = self::fromContainer(WooIndexSitemapRepository::class);
        $this->wooIndexNamer = self::fromContainer(WooIndexNamer::class);

        Assert::isInstanceOf(self::$kernel, KernelInterface::class);

        $this->application = new Application(self::$kernel);
        $this->application->setAutoExit(false);
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    public function testExecute(): void
    {
        $command = $this->application->find('woo-index:generate');
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['interactive' => false]);

        $output = $commandTester->getDisplay();

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Successfully published new sitemap with id: ', $output);
        $this->assertStringNotContainsString('Cleaning up older sitemaps', $output);

        $wooIndexSitemap = $this->wooIndexSitemapRepository->lastFinished();
        $this->assertNotNull($wooIndexSitemap, 'IndexSitemap exists');

        $subPath = $this->wooIndexNamer->getStorageSubpath($wooIndexSitemap);
        $this->assertTrue($this->wooIndexStorage->directoryExists($subPath), 'Sitemap directory exists');

        $sitemapIndexPath = $subPath . $this->wooIndexNamer->getSitemapIndexName();
        $this->assertTrue($this->wooIndexStorage->has($sitemapIndexPath), 'SitemapIndex exists');

        $sitemapOnePath = $subPath . $this->wooIndexNamer->getSitemapName(1);
        $this->assertTrue($this->wooIndexStorage->has($sitemapOnePath), 'Sitemap one exsits');

        $sitemapTwoPath = $subPath . $this->wooIndexNamer->getSitemapName(2);
        $this->assertFalse($this->wooIndexStorage->has($sitemapTwoPath), 'Sitemap two does not exsit');
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    public function testExecuteWithCleanup(): void
    {
        $command = $this->application->find('woo-index:generate');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--cleanup' => true], ['interactive' => false]);

        $output = $commandTester->getDisplay();

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Successfully published new sitemap with id: ', $output);
        $this->assertStringContainsString('Cleaning up older sitemaps', $output);

        $wooIndexSitemap = $this->wooIndexSitemapRepository->lastFinished();
        $this->assertNotNull($wooIndexSitemap, 'IndexSitemap exists');

        $subPath = $this->wooIndexNamer->getStorageSubpath($wooIndexSitemap);
        $this->assertTrue($this->wooIndexStorage->directoryExists($subPath), 'Sitemap directory exists');

        $sitemapIndexPath = $subPath . $this->wooIndexNamer->getSitemapIndexName();
        $this->assertTrue($this->wooIndexStorage->has($sitemapIndexPath), 'SitemapIndex exists');

        $sitemapOnePath = $subPath . $this->wooIndexNamer->getSitemapName(1);
        $this->assertTrue($this->wooIndexStorage->has($sitemapOnePath), 'Sitemap one exsits');

        $sitemapTwoPath = $subPath . $this->wooIndexNamer->getSitemapName(2);
        $this->assertFalse($this->wooIndexStorage->has($sitemapTwoPath), 'Sitemap two does not exsit');
    }
}
