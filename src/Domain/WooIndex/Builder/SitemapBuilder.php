<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Builder;

use App\Domain\WooIndex\WriterFactory\DiWooXMLWriter;
use App\Domain\WooIndex\WriterFactory\WriterFactory;

final class SitemapBuilder
{
    /**
     * @var ?\Closure(DiWooXMLWriter):void
     */
    private ?\Closure $writerFactoryConfigurator = null;

    public function __construct(
        private readonly WriterFactory $writerFactory,
    ) {
    }

    public function open(string $path): DiWooXMLWriter
    {
        $writer = $this->getWriter($path);

        $writer->startDocument(version: '1.0', encoding: 'utf-8');

        $schemaLocations = implode(separator: ' ', array: [
            'https://www.sitemaps.org/schemas/sitemap/0.9',
            'https://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd',
            'https://standaarden.overheid.nl/diwoo/metadata/',
            'https://standaarden.overheid.nl/diwoo/metadata/0.9.4/xsd/diwoo-metadata.xsd',
        ]);

        $writer->startElement(name: 'urlset');
        $writer->writeAttribute(name: 'xmlns', value: 'https://www.sitemaps.org/schemas/sitemap/0.9');
        $writer->writeAttribute(name: 'xmlns:xsi', value: 'https://www.w3.org/2001/XMLSchema-instance');
        $writer->writeAttribute(name: 'xmlns:diwoo', value: 'https://standaarden.overheid.nl/diwoo/metadata/');
        $writer->writeAttribute(name: 'xsi:schemaLocation', value: $schemaLocations);

        return $writer;
    }

    public function close(DiWooXMLWriter $writer): void
    {
        $writer->endElement(); // closes urlset-element

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

    private function getWriter(string $path): DiWooXMLWriter
    {
        $writer = $this->writerFactory->create($path);

        if ($this->writerFactoryConfigurator !== null) {
            $this->writerFactoryConfigurator->call($this, $writer);
        }

        return $writer;
    }
}
