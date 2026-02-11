<?php

declare(strict_types=1);

namespace Shared\Service\FileReader;

use PhpOffice\PhpSpreadsheet\Exception;
use RuntimeException;
use Symfony\Component\Process\Process;

use function fclose;
use function fgetcsv;
use function fopen;
use function in_array;
use function strtolower;
use function sys_get_temp_dir;
use function tempnam;

/**
 * Creates a file reader for CSV files.
 */
class ExcelCsvReaderFactory implements ReaderFactoryInterface
{
    use HeaderMappingTrait;

    /** @var string[] */
    protected $supportedMimeTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'application/vnd.oasis.opendocument.spreadsheet',
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
        $csvFilePath = tempnam(sys_get_temp_dir(), 'xls-convert-');
        if ($csvFilePath === false) {
            throw new RuntimeException('Failed to create temporary file');
        }

        // Convert to CSV file.
        $params = [
            '/usr/bin/xlsx2csv',
            $filepath,
            $csvFilePath,
        ];
        $process = new Process($params);
        $process->run();
        if ($process->getExitCode() !== 0) {
            $error = $process->getErrorOutput();
            throw new RuntimeException('Failed to create csv: ' . $error);
        }

        // From this point on we have a CSV file, and we can use the CsvReader.
        $handle = fopen($csvFilePath, 'r');
        if (! $handle) {
            throw new RuntimeException('Failed to open file: ' . $csvFilePath);
        }

        $headers = fgetcsv($handle);
        fclose($handle);

        if ($headers === false) {
            $headers = [];
        }

        $mapping = $this->resolveHeaderMapping($headers, $columnMappings);

        return new CsvReader($csvFilePath, $mapping);
    }
}
