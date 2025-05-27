<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Builder;

use App\Domain\WooIndex\Exception\WooIndexInvalidArgumentException;
use App\Domain\WooIndex\Producer\DiWooDocument;
use App\Domain\WooIndex\Producer\Url;

final readonly class SitemapUrlBuilder
{
    public function addUrl(DiWooXMLWriter $writer, Url $url): void
    {
        $writer->startElement(name: 'url');

        $writer->writeElement(name: 'loc', content: $url->loc);
        $writer->writeElement(name: 'lastmod', content: $url->lastmod->toDateString());
        $this->writeChangefreq($writer, $url);
        $this->writePriority($writer, $url);

        $this->writeDiWooDocument($writer, $url->diWooDocument);

        $writer->endElement(); // closes url-element
    }

    private function writeChangefreq(DiWooXMLWriter $writer, Url $url): void
    {
        if ($url->changefreq === null) {
            return;
        }

        $writer->writeElement(name: 'changefreq', content: $url->changefreq->value);
    }

    private function writePriority(DiWooXMLWriter $writer, Url $url): void
    {
        if ($url->priority === null) {
            return;
        }

        if ($url->priority < 0.0 || $url->priority > 1.0) {
            throw WooIndexInvalidArgumentException::invalidPriority($url->priority);
        }

        $writer->writeElement(name: 'priority', content: (string) $url->priority);
    }

    private function writeDiWooDocument(DiWooXMLWriter $writer, DiWooDocument $document): void
    {
        $writer->startDiWooElement(name: 'Document');
        $writer->startDiWooElement(name: 'DiWoo');

        $writer->writeDiWooElement(name: 'creatiedatum', content: $document->creatiedatum->toDateString());
        $this->writePublisher($writer, $document);
        $this->writeOfficieleTitel($writer, $document);
        $this->writeInformatieCategorie($writer, $document);
        $this->writeDocumentHandeling($writer, $document);
        $this->writeIsPartOf($writer, $document);
        $this->writeHasParts($writer, $document);

        $writer->endElement(); // closes DiWoo-element
        $writer->endElement(); // closes Document-element
    }

    private function writePublisher(DiWooXMLWriter $writer, DiWooDocument $document): void
    {
        $writer->startDiWooElement('publisher');

        $writer->writeAttribute(name: 'resource', value: $document->publisher->getResource());
        $writer->text($document->publisher->value);

        $writer->endElement();
    }

    private function writeOfficieleTitel(DiWooXMLWriter $writer, DiWooDocument $document): void
    {
        $writer->startDiWooElement(name: 'titelcollectie');
        $writer->writeDiWooElement(name: 'officieleTitel', content: $document->officieleTitel);
        $writer->endElement(); // closes titelcollectie-element
    }

    private function writeInformatieCategorie(DiWooXMLWriter $writer, DiWooDocument $document): void
    {
        $writer->startDiWooElement('classificatiecollectie');
        $writer->startDiWooElement('informatiecategorieen');

        $writer->startDiWooElement('informatiecategorie');
        $writer->writeAttribute(name: 'resource', value: $document->informatieCategorie->getResource());
        $writer->text($document->informatieCategorie->value);
        $writer->endElement();

        $writer->endElement(); // closes informatiecategorieen-element
        $writer->endElement(); // closes classificatiecollectie-element
    }

    private function writeDocumentHandeling(DiWooXMLWriter $writer, DiWooDocument $document): void
    {
        $writer->startDiWooElement('documenthandelingen');
        $writer->startDiWooElement('documenthandeling');

        $writer->startDiWooElement('soortHandeling');
        $writer->writeAttribute(name: 'resource', value: $document->documentHandeling->soortHandeling->getResource());
        $writer->text($document->documentHandeling->soortHandeling->value);
        $writer->endElement(); // closes soortHandeling-element

        $writer->writeDiWooElement(name: 'atTime', content: $document->documentHandeling->atTime->toIso8601String());

        $writer->endElement(); // closes documenthandeling-element
        $writer->endElement(); // closes documenthandelingen-element
    }

    private function writeIsPartOf(DiWooXMLWriter $writer, DiWooDocument $document): void
    {
        if ($document->isPartOf === null) {
            return;
        }

        $writer->startDiWooElement('isPartOf');

        $writer->writeAttribute(name: 'resource', value: $document->isPartOf->resource);
        $writer->text($document->isPartOf->officieleTitel);

        $writer->endElement();
    }

    private function writeHasParts(DiWooXMLWriter $writer, DiWooDocument $document): void
    {
        if ($document->hasParts === null || $document->hasParts->isEmpty()) {
            return;
        }

        $writer->startDiWooElement('hasParts');

        foreach ($document->hasParts as $urlReference) {
            $writer->startDiWooElement('hasPart');

            $writer->writeAttribute(name: 'resource', value: $urlReference->resource);
            $writer->text($urlReference->officieleTitel);

            $writer->endElement(); // closes hasPart-element
        }

        $writer->endElement(); // closes hasParts-element
    }
}
