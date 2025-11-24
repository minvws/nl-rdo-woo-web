<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Builder;

use Webmozart\Assert\Assert;

final class SitemapBuilder
{
    /**
     * @var ?\Closure(DiWooXMLWriter):void
     */
    private ?\Closure $writerFactoryConfigurator = null;

    /**
     * @param resource $stream
     */
    public function open($stream): DiWooXMLWriter
    {
        Assert::resource($stream);

        $writer = $this->getWriter($stream);

        $writer->startDocument(version: '1.0', encoding: 'utf-8');

        $schemaLocations = implode(separator: ' ', array: [
            'http://www.sitemaps.org/schemas/sitemap/0.9',
            'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd',
            'https://standaarden.overheid.nl/diwoo/metadata/',
            'https://standaarden.overheid.nl/diwoo/metadata/0.9.4/xsd/diwoo-metadata.xsd',
        ]);

        $writer->startElement(name: 'urlset');
        $writer->writeAttribute(name: 'xmlns', value: 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $writer->writeAttribute(name: 'xmlns:xsi', value: 'http://www.w3.org/2001/XMLSchema-instance');
        $writer->writeAttribute(name: 'xmlns:diwoo', value: 'https://standaarden.overheid.nl/diwoo/metadata/');
        $writer->writeAttribute(name: 'xsi:schemaLocation', value: $schemaLocations);

        return $writer;
    }

    public function closeFlush(DiWooXMLWriter $writer): void
    {
        $writer->endElement(); // closes urlset-element

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
    private function getWriter($stream): DiWooXMLWriter
    {
        $writer = DiWooXMLWriter::toStream($stream);

        if ($this->writerFactoryConfigurator !== null) {
            $this->writerFactoryConfigurator->call($this, $writer);
        }

        return $writer;
    }
}
