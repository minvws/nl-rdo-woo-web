<?php

declare(strict_types=1);

namespace Shared\Service\FileReader;

use PhpOffice\PhpSpreadsheet\Exception;

/**
 * Creates a file reader for CSV files.
 */
class CsvReaderFactory implements ReaderFactoryInterface
{
    use HeaderMappingTrait;

    /** @var string[] */
    protected $supportedMimeTypes = [
        'text/csv',
        'text/plain',
        'application/csv',
        'text/comma-separated-values',
        'text/tsv',
    ];

    public function supports(string $mimetype): bool
    {
        return in_array(strtolower($mimetype), $this->supportedMimeTypes, true);
    }

    /**
     * @throws Exception
     */
    public function createReader(string $filepath, ColumnMapping ...$columnMappings): FileReaderInterface
    {
        $handle = fopen($filepath, 'r');
        if (! $handle) {
            throw new \RuntimeException('Failed to open file: ' . $filepath);
        }
        $headers = fgetcsv($handle);
        fclose($handle);

        if ($headers === false) {
            $headers = [];
        }

        $mapping = $this->resolveHeaderMapping($headers, $columnMappings);

        return new CsvReader($filepath, $mapping);
    }
}
