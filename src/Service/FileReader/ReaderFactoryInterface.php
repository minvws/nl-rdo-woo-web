<?php

declare(strict_types=1);

namespace App\Service\FileReader;

interface ReaderFactoryInterface
{
    // Returns true when the factory can create a reader for the given mimetype.
    public function supports(string $mimetype): bool;

    // Creates a reader for the given filepath and column mappings.
    public function createReader(string $filepath, ColumnMapping ...$columnMappings): FileReaderInterface;
}
