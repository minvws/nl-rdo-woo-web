<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Builder;

use Webmozart\Assert\Assert;

final class SitemapIndexBuilder
{
    /**
     * @var ?\Closure(DiWooXMLWriter):void
     */
    protected ?\Closure $writerFactoryConfigurator = null;

    /**
     * @param resource $stream
     */
    public function open($stream): DiWooXMLWriter
    {
        Assert::resource($stream);

        $writer = $this->getWriter($stream);

        $writer->startDocument(version: '1.0', encoding: 'utf-8');

        $writer->startElement(name: 'sitemapindex');

        $writer->writeAttribute(name: 'xmlns', value: 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return $writer;
    }

    public function addSitemap(DiWooXMLWriter $writer, string $location): void
    {
        $writer->startElement(name: 'sitemap');

        $writer->writeElement(name: 'loc', content: $location);

        $writer->endElement(); // closes sitemap-element
    }

    public function closeFlush(DiWooXMLWriter $writer): void
    {
        $writer->endElement(); // closes sitemapindex-element

        $writer->endDocument();

        $writer->flush();
    }

    /**
     * @param ?\Closure(DiWooXMLWriter):void $closure
     */
    public function setXMLWriterConfigurator(?\Closure $closure): self
    {
        $this->writerFactoryConfigurator = $closure;

        return $this;
    }

    /**
     * @param resource $stream
     */
    protected function getWriter($stream): DiWooXMLWriter
    {
        $writer = DiWooXMLWriter::toStream($stream);

        if ($this->writerFactoryConfigurator !== null) {
            $this->writerFactoryConfigurator->call($this, $writer);
        }

        return $writer;
    }
}
