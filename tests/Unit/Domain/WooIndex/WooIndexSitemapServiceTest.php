<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\WooIndex;

use ArrayIterator;
use Carbon\CarbonImmutable;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\WooIndex\Exception\WooIndexFileNotFoundException;
use Shared\Domain\WooIndex\WooIndex;
use Shared\Domain\WooIndex\WooIndexNamer;
use Shared\Domain\WooIndex\WooIndexRunOptions;
use Shared\Domain\WooIndex\WooIndexSitemap;
use Shared\Domain\WooIndex\WooIndexSitemapRepository;
use Shared\Domain\WooIndex\WooIndexSitemapService;
use Shared\Domain\WooIndex\WooIndexSitemapStatus;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Uid\Uuid;

use function fopen;

final class WooIndexSitemapServiceTest extends UnitTestCase
{
    private WooIndexSitemap&MockInterface $wooIndexSitemap;
    private FilesystemOperator&MockInterface $wooIndexStorage;
    private WooIndexNamer&MockInterface $wooIndexNamer;
    private WooIndexSitemapRepository&MockInterface $wooIndexSitemapRepository;
    private UrlGenerator&MockInterface $urlGenerator;
    private WooIndexSitemapService $wooIndexSitemapService;
    private WooIndex&MockInterface $wooIndex;
    private string $publicBaseUrl = 'http://localhost';

    protected function setUp(): void
    {
        parent::setUp();

        $this->wooIndexSitemap = Mockery::mock(WooIndexSitemap::class);
        $this->wooIndexStorage = Mockery::mock(FilesystemOperator::class);
        $this->wooIndexNamer = Mockery::mock(WooIndexNamer::class);
        $this->wooIndexSitemapRepository = Mockery::mock(WooIndexSitemapRepository::class);
        $this->urlGenerator = Mockery::mock(UrlGenerator::class);
        $this->wooIndex = Mockery::mock(WooIndex::class);

        $this->wooIndexSitemapService = new WooIndexSitemapService(
            $this->wooIndexStorage,
            $this->wooIndexNamer,
            $this->wooIndexSitemapRepository,
            $this->urlGenerator,
            $this->wooIndex,
            $this->publicBaseUrl,
        );
    }

    public function testGenerateSitemap(): void
    {
        /** @var ?WooIndexSitemap $wooIndexSitemap */
        $wooIndexSitemap = null;

        $this->wooIndexSitemapRepository
            ->expects('save')
            ->with(Mockery::capture($wooIndexSitemap), true);

        $this->wooIndex
            ->expects('create')
            ->with(Mockery::on(function (WooIndexSitemap $i) use (&$wooIndexSitemap): bool {
                return $i === $wooIndexSitemap;
            }), Mockery::type(WooIndexRunOptions::class))
            ->andReturnTrue();

        $this->wooIndexSitemapRepository
            ->expects('save')
            ->with(Mockery::on(function (WooIndexSitemap $i) use (&$wooIndexSitemap): bool {
                if ($i->getStatus() !== WooIndexSitemapStatus::DONE) {
                    return false;
                }

                return $i === $wooIndexSitemap;
            }), true);

        $result = $this->wooIndexSitemapService->generateSitemap();

        $this->assertSame($wooIndexSitemap, $result);
    }

    public function testGetFileAsStream(): void
    {
        $this->wooIndexNamer->expects('getStorageSubpath')
            ->with($this->wooIndexSitemap)
            ->andReturn('uuid/');

        $fullpath = 'uuid/my-file.xml';

        $tempStream = fopen('php://temp', 'r');
        $this->wooIndexStorage->expects('readStream')
            ->with($fullpath)
            ->andReturn($tempStream);

        // Provided a fullpath, but it should only take the basename to provent path traversal:
        $path = '../../foobar/my-file.xml';

        $result = $this->wooIndexSitemapService->getFileAsStream($this->wooIndexSitemap, $path);

        $this->assertSame($tempStream, $result);
    }

    public function testGetFileAsStreamThrowsExceptionWhenNotAbleToReadStream(): void
    {
        $this->wooIndexSitemap->expects('getId')
            ->times(2)
            ->andReturn(Uuid::v6());

        $this->wooIndexNamer->expects('getStorageSubpath')
            ->with($this->wooIndexSitemap)
            ->andReturn('uuid/');

        $fullpath = 'uuid/my-file.xml';

        $this->wooIndexStorage->expects('readStream')
            ->with($fullpath)
            ->andThrow($previous = new UnableToReadFile());

        $file = 'my-file.xml';

        $this->expectExceptionObject(WooIndexFileNotFoundException::create($this->wooIndexSitemap, $file, $previous));

        $this->wooIndexSitemapService->getFileAsStream($this->wooIndexSitemap, $file);
    }

    public function testCurrentSitemapIndexUrl(): void
    {
        $this->wooIndexSitemap->expects('getId')
            ->andReturn($uuid = Uuid::v6());
        $this->wooIndexSitemapRepository->expects('lastFinished')
            ->andReturn($this->wooIndexSitemap);
        $this->wooIndexNamer->expects('getSitemapIndexName')
            ->andReturn($indexFilename = 'my-indexfilename.xml');

        $this->urlGenerator
            ->expects('generate')
            ->with(
                'app_woo_index_sitemap_download',
                [
                    'id' => $uuid->toRfc4122(),
                    'file' => $indexFilename,
                ],
            )
            ->andReturn($subpath = '/my-path/to/the-file.xml');

        $result = $this->wooIndexSitemapService->getCurrentSitemapIndexUrl();

        $this->assertSame($this->publicBaseUrl . $subpath, $result);
    }

    public function testCurrentSitemapIndexUrlWithoutAnyWooIndexSitemap(): void
    {
        $this->wooIndexSitemapRepository->expects('lastFinished')
            ->andReturnNull();

        $result = $this->wooIndexSitemapService->getCurrentSitemapIndexUrl();

        $this->assertNull($result);
    }

    public function testCleanupSitemap(): void
    {
        $this->wooIndexNamer->expects('getStorageSubpath')
            ->with($this->wooIndexSitemap)
            ->andReturn($path = 'my-path');

        $this->wooIndexStorage->expects('deleteDirectory')
            ->with($path);

        $this->wooIndexSitemapRepository->expects('remove')
            ->with($this->wooIndexSitemap, true);

        $this->wooIndexSitemapService->cleanupSitemap($this->wooIndexSitemap);
    }

    public function testCleanupSitemapShouldContinueWhenFailingToDeleteDirectory(): void
    {
        $this->wooIndexNamer->expects('getStorageSubpath')
            ->with($this->wooIndexSitemap)
            ->andReturn($path = 'my-path');

        $this->wooIndexStorage->expects('deleteDirectory')
            ->with($path)
            ->andThrow(new UnableToReadFile());

        $this->wooIndexSitemapRepository->expects('remove')
            ->with($this->wooIndexSitemap, true);

        $this->wooIndexSitemapService->cleanupSitemap($this->wooIndexSitemap);
    }

    public function testCleanupSitemaps(): void
    {
        $now = CarbonImmutable::parse('2025-02-18 10:00');
        CarbonImmutable::setTestNow($now);

        $wooIndexSitemaps = [
            Mockery::mock(WooIndexSitemap::class),
            Mockery::mock(WooIndexSitemap::class),
            Mockery::mock(WooIndexSitemap::class),
            Mockery::mock(WooIndexSitemap::class),
            Mockery::mock(WooIndexSitemap::class),
        ];

        $treshold = 3;
        $cleanupUnfinishedAfterDay = 2;

        $this->wooIndexSitemapRepository
            ->expects('getSitemapsForCleanup')
            ->with(
                $treshold,
                Mockery::on(fn (CarbonImmutable $givenDate): bool => $givenDate->eq($now->subDays($cleanupUnfinishedAfterDay))),
            )
            ->andReturn(new ArrayIterator($wooIndexSitemaps));

        $wooIndexSitemapService = Mockery::mock(WooIndexSitemapService::class, [
            $this->wooIndexStorage,
            $this->wooIndexNamer,
            $this->wooIndexSitemapRepository,
            $this->urlGenerator,
            $this->wooIndex,
            $this->publicBaseUrl,
        ])->makePartial();

        $wooIndexSitemapService->expects('cleanupSitemap')->with($wooIndexSitemaps[0]);
        $wooIndexSitemapService->expects('cleanupSitemap')->with($wooIndexSitemaps[1]);
        $wooIndexSitemapService->expects('cleanupSitemap')->with($wooIndexSitemaps[2]);
        $wooIndexSitemapService->expects('cleanupSitemap')->with($wooIndexSitemaps[3]);
        $wooIndexSitemapService->expects('cleanupSitemap')->with($wooIndexSitemaps[4]);

        $wooIndexSitemapService->cleanupSitemaps($treshold, $cleanupUnfinishedAfterDay);
    }

    public function testCleanupAllSitemaps(): void
    {
        $wooIndexSitemapService = Mockery::mock(WooIndexSitemapService::class, [
            $this->wooIndexStorage,
            $this->wooIndexNamer,
            $this->wooIndexSitemapRepository,
            $this->urlGenerator,
            $this->wooIndex,
            $this->publicBaseUrl,
        ])->makePartial();

        $wooIndexSitemapService->expects('cleanupSitemaps')
            ->with(0, 0);

        $wooIndexSitemapService->cleanupAllSitemaps();
    }
}
