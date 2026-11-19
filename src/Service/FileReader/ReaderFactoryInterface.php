<?php

declare(strict_types=1);

namespace Shared\Service\FileReader;

interface ReaderFactoryInterface
{
    // Returns true when the factory can create a reader for the given mimetype.
    public function supports(string $mimetype): bool;

    /**
     * @param resource $stream
     */
    public function createReader($stream, ColumnMapping ...$columnMappings): FileReaderInterface;
}
