<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex;

use Carbon\CarbonImmutable;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Shared\Controller\Public\WooIndexController;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\WooIndex\Exception\WooIndexFileNotFoundException;
use Shared\ValueObject\Url;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Webmozart\Assert\Assert;

use function basename;

readonly class WooIndexSitemapService
{
    public function __construct(
        #[Target('wooIndexStorage')]
        private FilesystemOperator $wooIndexStorage,
        private WooIndexNamer $wooIndexNamer,
        private WooIndexSitemapRepository $wooIndexSitemapRepository,
        private PublicUrlGenerator $publicUrlGenerator,
        private WooIndex $wooIndex,
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

    public function getCurrentSitemapIndexUrl(): ?Url
    {
        $wooIndexSitemap = $this->wooIndexSitemapRepository->lastFinished();
        if ($wooIndexSitemap === null) {
            return null;
        }

        return $this->publicUrlGenerator->buildUrlFromRoute(WooIndexController::ROUTE_NAME_WOO_INDEX_SITEMAP_DOWNLOAD, [
            'id' => $wooIndexSitemap->getId()->toRfc4122(),
            'file' => $this->wooIndexNamer->getSitemapIndexName(),
        ]);
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
