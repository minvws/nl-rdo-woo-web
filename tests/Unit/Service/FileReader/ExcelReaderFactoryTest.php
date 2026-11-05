<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\FileReader;

use Shared\Exception\Import\MissingHeadersException;
use Shared\Exception\Import\SpreadsheetCannotBeOpenedException;
use Shared\Service\FileReader\ColumnMapping;
use Shared\Service\FileReader\ExcelReaderFactory;
use Shared\Tests\Unit\UnitTestCase;

use function sprintf;

use const DIRECTORY_SEPARATOR;

class ExcelReaderFactoryTest extends UnitTestCase
{
    public function testCreateReaderFailsWhenSpreadsheetCannotBeOpened(): void
    {
        $filepath = sprintf('%s%s%s.xlsx', __DIR__, DIRECTORY_SEPARATOR, $this->getFaker()->uuid());

        $excelReaderFactory = new ExcelReaderFactory();

        self::expectException(SpreadsheetCannotBeOpenedException::class);

        $excelReaderFactory->createReader($filepath);
    }

    public function testCreateReaderFailsWhenRequiredHeaderIsMissing(): void
    {
        $columnName = $this->getFaker()->uuid();

        $excelReaderFactory = new ExcelReaderFactory();

        self::expectException(MissingHeadersException::class);

        $excelReaderFactory->createReader(
            sprintf('%s%sinventory-with-empty-row.xlsx', __DIR__, DIRECTORY_SEPARATOR),
            new ColumnMapping($columnName, true, [$columnName]),
        );
    }
}
