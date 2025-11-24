<?php

declare(strict_types=1);

namespace Shared\Service\FileReader;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Shared\Exception\FileReaderException;

/**
 * Creates instances of the ExcelReader. Uses ColumnMapping instances as input to parse and validate the headers.
 * The result is a lookup table to be able to fetch column values for each row easily.
 *
 * Leading numbers in Excel column names are ignore (stripped).
 * Leading and trailing whitespace and casing are also ignored when matching column names.
 */
class ExcelReaderFactory implements ReaderFactoryInterface
{
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
        try {
            $reader = IOFactory::createReaderForFile($filepath);

            // This saves a bit of memory. We don't need formatting details for cells, just the raw data.
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($filepath);

            // Assume only first worksheet
            $sheet = $spreadsheet->getSheet(0);
        } catch (\Exception $exception) {
            throw FileReaderException::forOpenSpreadsheetException($exception);
        }

        $mapping = $this->resolveHeaderMapping($sheet, $columnMappings);

        return new ExcelReader($sheet, $mapping);
    }

    /**
     * Resolve the header mapping into an array of mapped headers (name => column)
     * Will throw an exception for missing mandatory headers.
     *
     * @param ColumnMapping[] $columnMappings
     *
     * @throws FileReaderException|Exception
     */
    private function resolveHeaderMapping(Worksheet $sheet, array $columnMappings): HeaderMap
    {
        $mapping = [];
        foreach ($columnMappings as $columnMapping) {
            $mapping[$columnMapping->getName()] = $columnMapping;
        }

        $headerMapping = [];
        $missingHeaders = $mapping;

        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $columnName = strval($cell->getValue());
                $columnName = strtolower(trim($columnName));
                $columnName = trim(ltrim($columnName, '0123456789'));
                if ($columnName === '') {
                    continue;
                }

                foreach ($missingHeaders as $key => $mapping) {
                    if (in_array($columnName, $mapping->getColumnNames(), true)) {
                        $headerMapping[$key] = $cell->getColumn();
                        unset($missingHeaders[$key]);
                    }
                }
            }
        }

        $missingHeaders = array_filter(
            $missingHeaders,
            static fn (ColumnMapping $mapping): bool => $mapping->isRequired()
        );

        if (count($missingHeaders) > 0) {
            throw FileReaderException::forMissingHeaders(array_keys($missingHeaders));
        }

        return new HeaderMap($headerMapping);
    }
}
