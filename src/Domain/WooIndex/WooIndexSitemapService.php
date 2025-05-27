<?php

declare(strict_types=1);

namespace App\Domain\WooIndex;

use App\Domain\WooIndex\Exception\WooIndexFileNotFoundException;
use Carbon\CarbonImmutable;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

readonly class WooIndexSitemapService
{
    public function __construct(
        private FilesystemOperator $wooIndexStorage,
        private WooIndexNamer $wooIndexNamer,
        private WooIndexSitemapRepository $wooIndexSitemapRepository,
        private UrlGeneratorInterface $urlGenerator,
        private WooIndex $wooIndex,
        private string $publicBaseUrl,
    ) {
    }

    public function generateSitemap(): WooIndexSitemap
    {
        $wooIndexSitemap = new WooIndexSitemap();
        $this->wooIndexSitemapRepository->save($wooIndexSitemap, true);

        $this->wooIndex->create($wooIndexSitemap, new WooIndexRunOptions());

        $wooIndexSitemap->setStatus(WooIndexSitemapStatus::DONE);
        $this->wooIndexSitemapRepository->save($wooIndexSitemap, true);

        return $wooIndexSitemap;
    }

    /**
     * @return resource
     */
    public function getFileAsStream(WooIndexSitemap $wooIndexSitemap, string $file)
    {
        $fullPath = $this->wooIndexNamer->getStorageSubpath($wooIndexSitemap) . basename($file);

        try {
            $stream = $this->wooIndexStorage->readStream($fullPath);
        } catch (UnableToReadFile $e) {
            throw WooIndexFileNotFoundException::create($wooIndexSitemap, $file, $e);
        }

        Assert::resource($stream);

        return $stream;
    }

    public function getCurrentSitemapIndexUrl(): ?string
    {
        $wooIndexSitemap = $this->wooIndexSitemapRepository->lastFinished();
        if ($wooIndexSitemap === null) {
            return null;
        }

        $subpath = $this->urlGenerator->generate('app_woo_index_sitemap_download', [
            'id' => $wooIndexSitemap->getId()->toRfc4122(),
            'file' => $this->wooIndexNamer->getSitemapIndexName(),
        ]);

        return $this->publicBaseUrl . $subpath;
    }

    public function cleanupSitemap(WooIndexSitemap $wooIndexSitemap): void
    {
        try {
            $this->wooIndexStorage->deleteDirectory($this->wooIndexNamer->getStorageSubpath($wooIndexSitemap));
        } catch (UnableToReadFile) {
            // Do nothing when it fails. The directory might not exist.
        }

        $this->wooIndexSitemapRepository->remove($wooIndexSitemap, true);
    }

    public function cleanupSitemaps(int $treshold = 5, int $cleanupUnfinishedAfterDay = 3): void
    {
        $date = CarbonImmutable::now()->subDays($cleanupUnfinishedAfterDay);

        foreach ($this->wooIndexSitemapRepository->getSitemapsForCleanup($treshold, $date) as $wooIndexSitemap) {
            $this->cleanupSitemap($wooIndexSitemap);
        }
    }

    public function cleanupAllSitemaps(): void
    {
        $this->cleanupSitemaps(0, 0);
    }
}
