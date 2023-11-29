<?php

declare(strict_types=1);

namespace App\Service\Excel;

use App\Exception\ExcelReaderException;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Creates instances of the ExcelReader. Uses ColumnMapping instances as input to parse and validate the headers.
 * The result is a lookup table to be able to fetch column values for each row easily.
 *
 * Leading numbers in Excel column names are ignore (stripped).
 * Leading and trailing whitespace and casing are also ignored when matching column names.
 */
class ExcelReaderFactory
{
    /**
     * @throws Exception
     */
    public function getReader(string $filepath, ColumnMapping ...$columnMappings): ExcelReader
    {
        try {
            $reader = IOFactory::createReaderForFile($filepath);

            // This saves a bit of memory. We don't need formatting details for cells, just the raw data.
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($filepath);

            // Assume only first worksheet
            $sheet = $spreadsheet->getSheet(0);
        } catch (\Exception $exception) {
            throw ExcelReaderException::forOpenSpreadsheetException($exception);
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
     * @throws ExcelReaderException|Exception
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
                if (empty($columnName)) {
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
            throw ExcelReaderException::forMissingHeaders(array_keys($missingHeaders));
        }

        return new HeaderMap($headerMapping);
    }
}
