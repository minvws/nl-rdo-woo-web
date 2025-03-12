<?php

declare(strict_types=1);

namespace App\Domain\WooIndex;

use App\Domain\WooIndex\Builder\SitemapBuilder;
use App\Domain\WooIndex\Builder\SitemapIndexBuilder;
use App\Domain\WooIndex\Builder\SitemapUrlBuilder;
use App\Domain\WooIndex\Producer\ProducerSignal;
use App\Domain\WooIndex\Producer\UrlProducer;
use App\Service\Storage\LocalFilesystem;

final readonly class WooIndex
{
    public const MAX_SITEMAP_SIZE = 49 * 1024 * 1024;

    public function __construct(
        private LocalFilesystem $localFilesystem,
        private UrlProducer $urlProducer,
        private SitemapIndexBuilder $sitemapIndexBuilder,
        private SitemapBuilder $sitemapBuilder,
        private SitemapUrlBuilder $urlBuilder,
        private WooIndexNamer $wooIndexNamer,
        private string $sitemapBaseUrl,
    ) {
    }

    public function create(WooIndexRunOptions $options): string
    {
        $runId = $this->wooIndexNamer->getWooIndexRunId($options->pathSuffix);
        $tempDir = $this->createTempDir($runId);

        $sitemapIndexWriter = $this->sitemapIndexBuilder->open($this->getSitemapIndexFullPath($tempDir));

        $sitemapNumber = 0;
        foreach ($this->urlProducer->getChunked($options->chunkSize) as $sitemap) {
            $sitemapNumber++;

            $sitemapFullPath = $this->getSitemapFullPath($tempDir, $sitemapNumber);

            $sitemapWriter = $this->sitemapBuilder->open($sitemapFullPath);
            foreach ($sitemap as $url) {
                if ($this->isFileSizeReached($sitemapFullPath)) {
                    break;
                }

                $this->urlBuilder->addUrl($sitemapWriter, $url);
            }
            $this->sitemapBuilder->close($sitemapWriter);

            $this->sitemapIndexBuilder->addSitemap($sitemapIndexWriter, $this->getSitemapUri($runId, $sitemapNumber));

            // If the generator was not consumed fully, we will stop the current generator so
            // it's items (Uri's) will move to the next sitemap:
            if ($sitemap->valid()) {
                $sitemap->send(ProducerSignal::STOP_CHUNK);
            }
        }

        $this->sitemapIndexBuilder->close($sitemapIndexWriter);

        return $tempDir;
    }

    private function createTempDir(string $runId): string
    {
        $tempDir = $this->localFilesystem->createTempDir($runId);
        if ($tempDir === false) {
            throw DiWooRuntimeException::failedCreatingTempDir();
        }

        return $tempDir;
    }

    private function getSitemapIndexFullPath(string $path): string
    {
        return $this->wooIndexNamer->joinPaths(
            $path,
            $this->wooIndexNamer->getSitemapIndexName(),
        );
    }

    private function getSitemapFullPath(string $path, int $sitemapNumber): string
    {
        return $this->wooIndexNamer->joinPaths(
            $path,
            $this->wooIndexNamer->getSitemapName($sitemapNumber),
        );
    }

    private function getSitemapUri(string $runId, int $sitemapNumber): string
    {
        return $this->wooIndexNamer->joinPaths(
            $this->sitemapBaseUrl,
            $runId,
            $this->wooIndexNamer->getSitemapName($sitemapNumber),
        );
    }

    private function isFileSizeReached(string $sitemapFullPath): bool
    {
        $fileSize = $this->localFilesystem->getFileSize($sitemapFullPath);
        if ($fileSize === false) {
            throw DiWooRuntimeException::failedGettingFileSize($sitemapFullPath);
        }

        return $fileSize >= self::MAX_SITEMAP_SIZE;
    }
}
