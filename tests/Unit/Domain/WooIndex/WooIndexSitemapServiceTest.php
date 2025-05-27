<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WooIndex;

use App\Domain\WooIndex\Exception\WooIndexFileNotFoundException;
use App\Domain\WooIndex\WooIndex;
use App\Domain\WooIndex\WooIndexNamer;
use App\Domain\WooIndex\WooIndexRunOptions;
use App\Domain\WooIndex\WooIndexSitemap;
use App\Domain\WooIndex\WooIndexSitemapRepository;
use App\Domain\WooIndex\WooIndexSitemapService;
use App\Domain\WooIndex\WooIndexSitemapStatus;
use App\Tests\Unit\UnitTestCase;
use Carbon\CarbonImmutable;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Mockery\MockInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Uid\Uuid;

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

        $this->wooIndexSitemap = \Mockery::mock(WooIndexSitemap::class);
        $this->wooIndexStorage = \Mockery::mock(FilesystemOperator::class);
        $this->wooIndexNamer = \Mockery::mock(WooIndexNamer::class);
        $this->wooIndexSitemapRepository = \Mockery::mock(WooIndexSitemapRepository::class);
        $this->urlGenerator = \Mockery::mock(UrlGenerator::class);
        $this->wooIndex = \Mockery::mock(WooIndex::class);

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
            ->shouldReceive('save')
            ->with(\Mockery::capture($wooIndexSitemap), true)
            ->once();

        $this->wooIndex
            ->shouldReceive('create')
            ->with(\Mockery::on(function (WooIndexSitemap $i) use (&$wooIndexSitemap): bool {
                return $i === $wooIndexSitemap;
            }), \Mockery::type(WooIndexRunOptions::class))
            ->once()
            ->andReturnTrue();

        $this->wooIndexSitemapRepository
            ->shouldReceive('save')
            ->with(\Mockery::on(function (WooIndexSitemap $i) use (&$wooIndexSitemap): bool {
                if ($i->getStatus() !== WooIndexSitemapStatus::DONE) {
                    return false;
                }

                return $i === $wooIndexSitemap;
            }), true)
            ->once();

        $result = $this->wooIndexSitemapService->generateSitemap();

        $this->assertSame($wooIndexSitemap, $result);
    }

    public function testGetFileAsStream(): void
    {
        $this->wooIndexNamer->shouldReceive('getStorageSubpath')->with($this->wooIndexSitemap)->andReturn('uuid/');

        $fullpath = 'uuid/my-file.xml';

        $tempStream = fopen('php://temp', 'r');
        $this->wooIndexStorage->shouldReceive('readStream')->with($fullpath)->once()->andReturn($tempStream);

        // Provided a fullpath, but it should only take the basename to provent path traversal:
        $path = '../../foobar/my-file.xml';

        $result = $this->wooIndexSitemapService->getFileAsStream($this->wooIndexSitemap, $path);

        $this->assertSame($tempStream, $result);
    }

    public function testGetFileAsStreamThrowsExceptionWhenNotAbleToReadStream(): void
    {
        $this->wooIndexSitemap->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->wooIndexNamer->shouldReceive('getStorageSubpath')->with($this->wooIndexSitemap)->andReturn('uuid/');

        $fullpath = 'uuid/my-file.xml';

        $this->wooIndexStorage->shouldReceive('readStream')->with($fullpath)->once()->andThrow($previous = new UnableToReadFile());

        $file = 'my-file.xml';

        $this->expectExceptionObject(WooIndexFileNotFoundException::create($this->wooIndexSitemap, $file, $previous));

        $this->wooIndexSitemapService->getFileAsStream($this->wooIndexSitemap, $file);
    }

    public function testCurrentSitemapIndexUrl(): void
    {
        $this->wooIndexSitemap->shouldReceive('getId')->andReturn($uuid = Uuid::v6());
        $this->wooIndexSitemapRepository->shouldReceive('lastFinished')->andReturn($this->wooIndexSitemap);
        $this->wooIndexNamer->shouldReceive('getSitemapIndexName')->andReturn($indexFilename = 'my-indexfilename.xml');

        $this->urlGenerator
            ->shouldReceive('generate')
            ->with(
                'app_woo_index_sitemap_download',
                [
                    'id' => $uuid->toRfc4122(),
                    'file' => $indexFilename,
                ],
            )
            ->once()
            ->andReturn($subpath = '/my-path/to/the-file.xml');

        $result = $this->wooIndexSitemapService->getCurrentSitemapIndexUrl();

        $this->assertSame($this->publicBaseUrl . $subpath, $result);
    }

    public function testCurrentSitemapIndexUrlWithoutAnyWooIndexSitemap(): void
    {
        $this->wooIndexSitemapRepository->shouldReceive('lastFinished')->andReturnNull();

        $result = $this->wooIndexSitemapService->getCurrentSitemapIndexUrl();

        $this->assertNull($result);
    }

    public function testCleanupSitemap(): void
    {
        $this->wooIndexNamer->shouldReceive('getStorageSubpath')->with($this->wooIndexSitemap)->andReturn($path = 'my-path');

        $this->wooIndexStorage->shouldReceive('deleteDirectory')->with($path)->once();

        $this->wooIndexSitemapRepository->shouldReceive('remove')->with($this->wooIndexSitemap, true)->once();

        $this->wooIndexSitemapService->cleanupSitemap($this->wooIndexSitemap);
    }

    public function testCleanupSitemapShouldContinueWhenFailingToDeleteDirectory(): void
    {
        $this->wooIndexNamer->shouldReceive('getStorageSubpath')->with($this->wooIndexSitemap)->andReturn($path = 'my-path');

        $this->wooIndexStorage->shouldReceive('deleteDirectory')->with($path)->once()->andThrow(new UnableToReadFile());

        $this->wooIndexSitemapRepository->shouldReceive('remove')->with($this->wooIndexSitemap, true)->once();

        $this->wooIndexSitemapService->cleanupSitemap($this->wooIndexSitemap);
    }

    public function testCleanupSitemaps(): void
    {
        $this->setTestNow($now = CarbonImmutable::parse('2025-02-18 10:00'));

        $wooIndexSitemaps = [
            \Mockery::mock(WooIndexSitemap::class),
            \Mockery::mock(WooIndexSitemap::class),
            \Mockery::mock(WooIndexSitemap::class),
            \Mockery::mock(WooIndexSitemap::class),
            \Mockery::mock(WooIndexSitemap::class),
        ];

        $treshold = 3;
        $cleanupUnfinishedAfterDay = 2;

        $this->wooIndexSitemapRepository
            ->shouldReceive('getSitemapsForCleanup')
            ->with(
                $treshold,
                \Mockery::on(fn (CarbonImmutable $givenDate): bool => $givenDate->eq($now->subDays($cleanupUnfinishedAfterDay))),
            )
            ->once()
            ->andReturn(new \ArrayIterator($wooIndexSitemaps));

        /** @var WooIndexSitemapService&MockInterface $wooIndexSitemapService */
        $wooIndexSitemapService = \Mockery::mock(WooIndexSitemapService::class, [
            $this->wooIndexStorage,
            $this->wooIndexNamer,
            $this->wooIndexSitemapRepository,
            $this->urlGenerator,
            $this->wooIndex,
            $this->publicBaseUrl,
        ])->makePartial();

        $wooIndexSitemapService->shouldReceive('cleanupSitemap')->with($wooIndexSitemaps[0])->once();
        $wooIndexSitemapService->shouldReceive('cleanupSitemap')->with($wooIndexSitemaps[1])->once();
        $wooIndexSitemapService->shouldReceive('cleanupSitemap')->with($wooIndexSitemaps[2])->once();
        $wooIndexSitemapService->shouldReceive('cleanupSitemap')->with($wooIndexSitemaps[3])->once();
        $wooIndexSitemapService->shouldReceive('cleanupSitemap')->with($wooIndexSitemaps[4])->once();

        $wooIndexSitemapService->cleanupSitemaps($treshold, $cleanupUnfinishedAfterDay);
    }

    public function testCleanupAllSitemaps(): void
    {
        /** @var WooIndexSitemapService&MockInterface $wooIndexSitemapService */
        $wooIndexSitemapService = \Mockery::mock(WooIndexSitemapService::class, [
            $this->wooIndexStorage,
            $this->wooIndexNamer,
            $this->wooIndexSitemapRepository,
            $this->urlGenerator,
            $this->wooIndex,
            $this->publicBaseUrl,
        ])->makePartial();

        $wooIndexSitemapService->shouldReceive('cleanupSitemaps')->with(0, 0)->once();

        $wooIndexSitemapService->cleanupAllSitemaps();
    }
}
