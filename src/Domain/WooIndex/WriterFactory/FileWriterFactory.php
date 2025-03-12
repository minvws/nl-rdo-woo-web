<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\WriterFactory;

readonly class FileWriterFactory implements WriterFactory
{
    public function create(string $path): DiWooXMLWriter
    {
        $writer = $this->newXmlWriter();
        if (! $writer->openUri($path)) {
            throw FailedCreatingXmlException::create($path, self::class);
        }

        return $writer;
    }

    protected function newXmlWriter(): DiWooXMLWriter
    {
        return new DiWooXMLWriter();
    }
}
