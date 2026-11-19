<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\SpreadsheetTable;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\BaseReader;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Shared\Domain\Upload\FileType\FileExtension;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function count;
use function implode;
use function in_array;
use function trim;

readonly class SpreadsheetTableFactory
{
    public function __construct(
        private SpreadsheetReaderFactory $spreadsheetReaderFactory,
        #[Autowire(param: 'spreadsheet_table.max_rows')]
        private int $maxRows,
        #[Autowire(param: 'spreadsheet_table.max_columns')]
        private int $maxColumns,
    ) {
    }

    public function supports(FileExtension $fileExtension): bool
    {
        $allowedExtensions = [
            FileExtension::XLS,
            FileExtension::XLSX,
        ];

        return in_array($fileExtension, $allowedExtensions, true);
    }

    /**
     * @throws SpreadsheetTableException
     */
    public function create(string $path, FileExtension $fileExtension): SpreadsheetTableResult
    {
        $reader = $this->spreadsheetReaderFactory->create($fileExtension);

        try {
            $worksheetInfo = $reader->listWorksheetInfo($path);
            if (count($worksheetInfo) > 1) {
                throw SpreadsheetTableException::forMultipleWorksheets();
            }

            return new SpreadsheetTableResult(
                $this->getSpreadsheetTable($reader, $path),
                $this->isTruncated($worksheetInfo[0]['totalRows'], $worksheetInfo[0]['totalColumns']),
            );
        } catch (PhpSpreadsheetException) {
            throw SpreadsheetTableException::forUnreadableFile();
        }
    }

    private function getSpreadsheetTable(BaseReader $reader, string $path): SpreadsheetTable
    {
        $reader->setReadFilter(new SpreadsheetTableReadFilter($this->maxRows + 1, $this->maxColumns));
        $worksheet = $reader->load($path)->getSheet(0);

        $columnCount = Coordinate::columnIndexFromString($worksheet->getHighestDataColumn());

        $columns = $this->readRow($worksheet, 1, $columnCount);
        if (in_array('', $columns, true)) {
            throw SpreadsheetTableException::forMissingHeaderRow();
        }

        $rows = [];
        $highestDataRow = $worksheet->getHighestDataRow();
        for ($row = 2; $row <= $highestDataRow; $row++) {
            $values = $this->readRow($worksheet, $row, $columnCount);
            if ($this->isEmptyRow($values)) {
                continue;
            }

            $rows[] = $values;
        }

        return new SpreadsheetTable($columns, $rows);
    }

    /**
     * @return list<string>
     */
    private function readRow(Worksheet $worksheet, int $row, int $columnCount): array
    {
        $values = [];
        for ($column = 1; $column <= $columnCount; $column++) {
            $values[] = $this->readCell($worksheet, $column, $row);
        }

        return $values;
    }

    /**
     * @param list<string> $values
     */
    private function isEmptyRow(array $values): bool
    {
        return implode('', $values) === '';
    }

    private function readCell(Worksheet $worksheet, int $column, int $row): string
    {
        if (! $worksheet->cellExists([$column, $row])) {
            return '';
        }

        $value = $worksheet->getCell([$column, $row])
            ->getFormattedValue();

        return trim($value);
    }

    private function isTruncated(int $totalRows, int $totalColumns): bool
    {
        if ($totalRows > $this->maxRows + 1) {
            return true;
        }

        if ($totalColumns > $this->maxColumns) {
            return true;
        }

        return false;
    }
}
