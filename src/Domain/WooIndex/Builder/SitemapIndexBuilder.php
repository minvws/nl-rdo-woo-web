<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Builder;

use App\Domain\WooIndex\WriterFactory\DiWooXMLWriter;
use App\Domain\WooIndex\WriterFactory\WriterFactory;

final class SitemapIndexBuilder
{
    /**
     * @var ?\Closure(DiWooXMLWriter):void
     */
    protected ?\Closure $writerFactoryConfigurator = null;

    public function __construct(
        private readonly WriterFactory $writerFactory,
    ) {
    }

    public function open(string $path): DiWooXMLWriter
    {
        $writer = $this->getWriter($path);

        $writer->startDocument(version: '1.0', encoding: 'utf-8');

        $writer->startElement(name: 'sitemapindex');

        $writer->writeAttribute(name: 'xmlns', value: 'https://www.sitemaps.org/schemas/sitemap/0.9');

        return $writer;
    }

    public function addSitemap(DiWooXMLWriter $writer, string $location): void
    {
        $writer->startElement(name: 'sitemap');

        $writer->writeElement(name: 'loc', content: $location);

        $writer->endElement(); // closes sitemap-element
    }

    public function close(DiWooXMLWriter $writer): void
    {
        $writer->endElement(); // closes sitemapindex-element

        $writer->endDocument();
    }

    /**
     * @param ?\Closure(DiWooXMLWriter):void $closure
     */
    public function setXMLWriterConfigurator(?\Closure $closure): self
    {
        $this->writerFactoryConfigurator = $closure;

        return $this;
    }

    protected function getWriter(string $path): DiWooXMLWriter
    {
        $writer = $this->writerFactory->create($path);

        if ($this->writerFactoryConfigurator !== null) {
            $this->writerFactoryConfigurator->call($this, $writer);
        }

        return $writer;
    }
}
