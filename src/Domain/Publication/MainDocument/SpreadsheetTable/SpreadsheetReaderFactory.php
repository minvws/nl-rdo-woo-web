<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\SpreadsheetTable;

use PhpOffice\PhpSpreadsheet\Reader\BaseReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Shared\Domain\Upload\FileType\FileExtension;

readonly class SpreadsheetReaderFactory
{
    public function create(FileExtension $fileExtension): BaseReader
    {
        return match ($fileExtension) {
            FileExtension::XLS => new Xls(),
            FileExtension::XLSX => new Xlsx(),
            default => throw SpreadsheetTableException::forUnsupportedExtension(),
        };
    }
}
