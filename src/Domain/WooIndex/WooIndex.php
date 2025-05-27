<?php

declare(strict_types=1);

namespace App\Domain\WooIndex;

use App\Domain\WooIndex\Builder\SitemapBuilder;
use App\Domain\WooIndex\Builder\SitemapIndexBuilder;
use App\Domain\WooIndex\Builder\SitemapUrlBuilder;
use App\Domain\WooIndex\Producer\ProducerSignal;
use App\Domain\WooIndex\Producer\UrlProducer;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class WooIndex
{
    public const MAX_SITEMAP_SIZE = 49 * 1024 * 1024;

    public function __construct(
        private FilesystemOperator $wooIndexStorage,
        private UrlProducer $urlProducer,
        private SitemapIndexBuilder $sitemapIndexBuilder,
        private SitemapBuilder $sitemapBuilder,
        private SitemapUrlBuilder $urlBuilder,
        private WooIndexNamer $wooIndexNamer,
        private UrlGeneratorInterface $urlGenerator,
        private StreamHelper $streamHelper,
        private string $publicBaseUrl,
    ) {
    }

    public function create(WooIndexSitemap $wooIndexSitemap, WooIndexRunOptions $options): void
    {
        $subPath = $this->wooIndexNamer->getStorageSubpath($wooIndexSitemap);

        $sitemapIndexStream = $this->streamHelper->createTempStream();

        $sitemapIndexWriter = $this->sitemapIndexBuilder->open($sitemapIndexStream);

        $sitemapNumber = 0;
        foreach ($this->urlProducer->getChunked($options->chunkSize) as $sitemap) {
            $sitemapNumber++;

            $sitemapStream = $this->streamHelper->createTempStream();

            $sitemapWriter = $this->sitemapBuilder->open($sitemapStream);
            foreach ($sitemap as $url) {
                // Write writer buffer to stream before checking stream size.
                $sitemapWriter->flush();

                if ($this->isFileSizeReached($sitemapStream)) {
                    break;
                }

                $this->urlBuilder->addUrl($sitemapWriter, $url);
            }
            $this->sitemapBuilder->closeFlush($sitemapWriter);
            $this->rewindWriteToStorageAndCloseStream($this->getSitemapFullPath($subPath, $sitemapNumber), $sitemapStream);

            $this->sitemapIndexBuilder->addSitemap($sitemapIndexWriter, $this->getSitemapUri($wooIndexSitemap, $sitemapNumber));

            // If the generator was not consumed fully, we will stop the current generator so
            // it's items (Uri's) will move to the next sitemap:
            if ($sitemap->valid()) {
                $sitemap->send(ProducerSignal::STOP_CHUNK);
            }
        }

        $this->sitemapIndexBuilder->closeFlush($sitemapIndexWriter);
        $this->rewindWriteToStorageAndCloseStream($this->getSitemapIndexFullPath($subPath), $sitemapIndexStream);
    }

    private function getSitemapIndexFullPath(string $path): string
    {
        return sprintf('%s/%s', $path, $this->wooIndexNamer->getSitemapIndexName());
    }

    private function getSitemapFullPath(string $path, int $sitemapNumber): string
    {
        return sprintf('%s/%s', $path, $this->wooIndexNamer->getSitemapName($sitemapNumber));
    }

    private function getSitemapUri(WooIndexSitemap $wooIndexSitemap, int $sitemapNumber): string
    {
        $subpath = $this->urlGenerator->generate('app_woo_index_sitemap_download', [
            'id' => $wooIndexSitemap->getId()->toRfc4122(),
            'file' => $this->wooIndexNamer->getSitemapName($sitemapNumber),
        ]);

        return $this->publicBaseUrl . $subpath;
    }

    /**
     * @param resource $contents
     */
    private function rewindWriteToStorageAndCloseStream(string $location, $contents): void
    {
        if (ftell($contents) !== 0) {
            rewind($contents);
        }

        $this->wooIndexStorage->writeStream($location, $contents);

        if (is_resource($contents)) {
            fclose($contents);
        }
    }

    /**
     * @param resource $stream
     */
    private function isFileSizeReached($stream): bool
    {
        return $this->streamHelper->size($stream) >= self::MAX_SITEMAP_SIZE;
    }
}
