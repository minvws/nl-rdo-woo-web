<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\WriterFactory;

readonly class MemoryWriterFactory implements WriterFactory
{
    public function create(string $path): DiWooXMLWriter
    {
        $writer = $this->newXmlWriter();
        if (! $writer->openMemory()) {
            throw FailedCreatingXmlException::create('in-memory', self::class);
        }

        return $writer;
    }

    protected function newXmlWriter(): DiWooXMLWriter
    {
        return new DiWooXMLWriter();
    }
}
