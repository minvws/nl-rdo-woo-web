<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\WooIndex;

use App\Domain\WooIndex\Builder\SitemapBuilder;
use App\Domain\WooIndex\Builder\SitemapIndexBuilder;
use App\Domain\WooIndex\DiWooRuntimeException;
use App\Domain\WooIndex\WooIndex;
use App\Domain\WooIndex\WooIndexRunOptions;
use App\Domain\WooIndex\WriterFactory\DiWooXMLWriter;
use App\Service\Storage\LocalFilesystem;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Story\WooIndexAnnualReportStory;
use App\Tests\Story\WooIndexCovenantStory;
use App\Tests\Story\WooIndexWooDecisionStory;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class WooIndexTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private vfsStreamDirectory $root;

    private LoggerInterface $logger;

    private LocalFilesystem&MockInterface $localFilesystem;

    private WooIndex $wooIndex;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->root = vfsStream::setup();

        $this->logger = self::getContainer()->get(LoggerInterface::class);

        $this->localFilesystem = $this->getPartialLocalFilesystem();
        self::getContainer()->set(LocalFilesystem::class, $this->localFilesystem);

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

        $options = new WooIndexRunOptions(
            chunkSize: 2,
            pathSuffix: 'random-string',
        );
        $path = $this->wooIndex->create($options);

        $sitemapIndexPath = sprintf('%s/sitemap-index.xml', $path);
        $this->assertFileExists($sitemapIndexPath);
        $this->assertMatchesFileSnapshot($sitemapIndexPath);

        foreach (range(1, 9) as $sitemapNumber) {
            $sitemapPath = sprintf('%s/sitemap-%05d.xml', $path, $sitemapNumber);
            $this->assertFileExists($sitemapPath);
            $this->assertMatchesFileSnapshot($sitemapPath);
        }
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    public function testCreateWithOneSitemapReachingFileSize(): void
    {
        $this->setTestNow('2025-01-01 00:00:00');

        // On the 7th iteration we will reach the file size limit, the rest will return 0 bytes
        $this->localFilesystem->shouldReceive('getFileSize')->andReturnValues([0, 0, 0, 0, 0, 0, 49 * 1024 * 1024 + 1, 0]);

        $options = new WooIndexRunOptions(
            chunkSize: 4,
            pathSuffix: 'random-string',
        );
        $path = $this->wooIndex->create($options);

        $sitemapIndexPath = sprintf('%s/sitemap-index.xml', $path);
        $this->assertFileExists($sitemapIndexPath);
        $this->assertMatchesFileSnapshot($sitemapIndexPath);

        foreach (range(1, 3) as $sitemapNumber) {
            $sitemapPath = sprintf('%s/sitemap-%05d.xml', $path, $sitemapNumber);
            $this->assertFileExists($sitemapPath);
            $this->assertMatchesFileSnapshot($sitemapPath);
        }
    }

    public function testCreateThrowsExceptionWhenItFailsToCreateTempDir(): void
    {
        $this->localFilesystem->shouldReceive('createTempDir')->andReturn(false);

        $this->expectExceptionObject(DiWooRuntimeException::failedCreatingTempDir());

        $options = new WooIndexRunOptions(
            chunkSize: 2,
            pathSuffix: 'random-string',
        );
        $this->wooIndex->create($options);
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    public function testCreateThrowsExceptonWhenItFailsToGetFileSize(): void
    {
        $this->setTestNow('2025-01-01 00:00:00');

        $this->localFilesystem->shouldReceive('getFileSize')->andReturn(false);
        $this->localFilesystem->shouldReceive('uniqid')->andReturn('uniqid');

        $expectedPath = 'vfs://root/tmp/uniqid/20250101_120100_000000__random-string/sitemap-00001.xml';

        $this->expectExceptionObject(DiWooRuntimeException::failedGettingFileSize($expectedPath));

        $options = new WooIndexRunOptions(
            chunkSize: 2,
            pathSuffix: 'random-string',
        );
        $this->wooIndex->create($options);
    }

    private function getPartialLocalFilesystem(): LocalFilesystem&MockInterface
    {
        vfsStream::newDirectory('tmp')->at($this->root);

        $localFilesystem = \Mockery::mock(LocalFilesystem::class, [$this->logger])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $localFilesystem->shouldReceive('sysGetTempDir')->andReturn('vfs://root/tmp');

        return $localFilesystem;
    }
}
