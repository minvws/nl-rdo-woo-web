<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use function implode;
use function is_array;

class ExcelWriter implements InventoryWriterInterface
{
    private string $filename;
    private Spreadsheet $spreadsheet;
    private Worksheet $workSheet;
    private int $rowNr = 1;

    public function open(string $filename): void
    {
        $this->filename = $filename;
        $this->spreadsheet = new Spreadsheet();
        $this->workSheet = $this->spreadsheet->getActiveSheet();
    }

    public function addHeaders(string ...$headers): void
    {
        $this->workSheet->fromArray([$headers]);
        $this->rowNr = 2;
    }

    /**
     * @param array<string>|string ...$cells
     */
    public function addRow(mixed ...$cells): void
    {
        foreach ($cells as $key => $value) {
            if (is_array($value)) {
                $cells[$key] = implode(';', $value);
            }
        }

        $this->workSheet->fromArray([$cells], null, 'A' . $this->rowNr);
        $this->rowNr++;
    }

    public function close(): void
    {
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($this->filename);
    }

    public function getFileExtension(): string
    {
        return 'xlsx';
    }
}
