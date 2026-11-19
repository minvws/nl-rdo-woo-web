<?php

declare(strict_types=1);

namespace Shared\Service\FileReader;

use PhpOffice\PhpSpreadsheet\Exception;
use Webmozart\Assert\Assert;

use function fgetcsv;
use function in_array;
use function rewind;
use function strtolower;

/**
 * Creates a file reader for CSV files.
 */
class CsvReaderFactory implements ReaderFactoryInterface
{
    use HeaderMappingTrait;

    /** @var array<array-key, string> */
    protected array $supportedMimeTypes = [
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
     * @param resource $stream
     *
     * @throws Exception
     */
    public function createReader($stream, ColumnMapping ...$columnMappings): FileReaderInterface
    {
        $headers = fgetcsv($stream, escape: '\\');
        rewind($stream);

        if ($headers === false) {
            $headers = [];
        }
        Assert::allString($headers);

        $mapping = $this->resolveHeaderMapping($headers, $columnMappings);

        return new CsvReader($stream, $mapping);
    }
}
