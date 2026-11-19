<?php

declare(strict_types=1);

namespace Shared\Service\FileReader;

use GuzzleHttp\Psr7\Utils;
use PhpOffice\PhpSpreadsheet\Exception;
use RuntimeException;
use Shared\Service\ProcessFactory;
use Webmozart\Assert\Assert;

use function fgetcsv;
use function fwrite;
use function in_array;
use function rewind;
use function sprintf;
use function strtolower;

/**
 * Creates a file reader for CSV files.
 */
class ExcelCsvReaderFactory implements ReaderFactoryInterface
{
    use HeaderMappingTrait;

    public const string XLSX2CSV_PATH = '/usr/bin/xlsx2csv';
    public const string INPUT_FROM_STDIN = '-';

    /** @var array<array-key, string> */
    protected array $supportedMimeTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'application/vnd.oasis.opendocument.spreadsheet',
    ];

    public function __construct(
        private readonly ProcessFactory $processFactory,
    ) {
    }

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
        $process = $this->processFactory->create([self::XLSX2CSV_PATH, self::INPUT_FROM_STDIN]);
        $process->setInput($stream);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new RuntimeException(sprintf('Failed to create csv: %s', $process->getErrorOutput()));
        }

        $csv = Utils::tryFopen('php://memory', 'r+');
        fwrite($csv, $process->getOutput());
        rewind($csv);

        $headers = fgetcsv($csv, escape: '\\');
        rewind($csv);

        if ($headers === false) {
            $headers = [];
        }
        Assert::allString($headers);

        $mapping = $this->resolveHeaderMapping($headers, $columnMappings);

        return new CsvReader($csv, $mapping);
    }
}
