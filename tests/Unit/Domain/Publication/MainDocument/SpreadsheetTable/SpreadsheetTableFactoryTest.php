<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\MainDocument\SpreadsheetTable;

use Mockery;
use PhpOffice\PhpSpreadsheet\Reader\BaseReader;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetReaderFactory;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTable;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableException;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableFactory;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableReadFilter;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableResult;
use Shared\Domain\Upload\FileType\FileExtension;
use Shared\Tests\Unit\UnitTestCase;

final class SpreadsheetTableFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $path = $this->getFaker()->word();

        $header1 = $this->getFaker()->unique()->word();
        $header2 = $this->getFaker()->unique()->word();

        $value1 = $this->getFaker()->unique()->word();
        $value2 = $this->getFaker()->unique()->word();
        $value3 = $this->getFaker()->unique()->word();
        $value4 = $this->getFaker()->unique()->word();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            [$header1, $header2],
            [$value1, $value2],
            [$value3, $value4],
        ]);

        $reader = Mockery::mock(BaseReader::class);
        $reader->expects('listWorksheetInfo')
            ->with($path)
            ->andReturn([['totalRows' => 3, 'totalColumns' => 2]]);
        $reader->expects('setReadFilter')
            ->with(Mockery::type(SpreadsheetTableReadFilter::class))
            ->andReturnSelf();
        $reader->expects('load')
            ->with($path)
            ->andReturn($spreadsheet);

        $spreadsheetReaderFactory = Mockery::mock(SpreadsheetReaderFactory::class);
        $spreadsheetReaderFactory->expects('create')
            ->with(FileExtension::XLSX)
            ->andReturn($reader);

        $spreadsheetTableFactory = new SpreadsheetTableFactory(
            $spreadsheetReaderFactory,
            $this->getFaker()->numberBetween(10, 100),
            $this->getFaker()->numberBetween(10, 100),
        );

        self::assertEquals(
            new SpreadsheetTableResult(
                new SpreadsheetTable(
                    [$header1, $header2],
                    [[$value1, $value2], [$value3, $value4]],
                ),
                false,
            ),
            $spreadsheetTableFactory->create($path, FileExtension::XLSX),
        );
    }

    public function testCreateSkipsEmptyRows(): void
    {
        $path = $this->getFaker()->word();

        $header1 = $this->getFaker()->unique()->word();
        $header2 = $this->getFaker()->unique()->word();

        $value1 = $this->getFaker()->unique()->word();
        $value2 = $this->getFaker()->unique()->word();
        $value3 = $this->getFaker()->unique()->word();
        $value4 = $this->getFaker()->unique()->word();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            [$header1, $header2],
            [$value1, $value2],
            ['', ''],
            [$value3, $value4],
            ['', ' '],
        ]);

        $reader = Mockery::mock(BaseReader::class);
        $reader->expects('listWorksheetInfo')
            ->with($path)
            ->andReturn([['totalRows' => 5, 'totalColumns' => 2]]);
        $reader->expects('setReadFilter')
            ->with(Mockery::type(SpreadsheetTableReadFilter::class))
            ->andReturnSelf();
        $reader->expects('load')
            ->with($path)
            ->andReturn($spreadsheet);

        $spreadsheetReaderFactory = Mockery::mock(SpreadsheetReaderFactory::class);
        $spreadsheetReaderFactory->expects('create')
            ->with(FileExtension::XLSX)
            ->andReturn($reader);

        $spreadsheetTableFactory = new SpreadsheetTableFactory(
            $spreadsheetReaderFactory,
            $this->getFaker()->numberBetween(10, 100),
            $this->getFaker()->numberBetween(10, 100),
        );

        self::assertEquals(
            new SpreadsheetTableResult(
                new SpreadsheetTable(
                    [$header1, $header2],
                    [[$value1, $value2], [$value3, $value4]],
                ),
                false,
            ),
            $spreadsheetTableFactory->create($path, FileExtension::XLSX),
        );
    }

    public function testCreateWhenTooManyRows(): void
    {
        $path = $this->getFaker()->word();
        $header = $this->getFaker()->unique()->word();
        $value = $this->getFaker()->unique()->word();
        $maxRows = $this->getFaker()->numberBetween(1, 10);
        $totalRows = $this->getFaker()->numberBetween(12, 20);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            [$header],
            [$value],
        ]);

        $reader = Mockery::mock(BaseReader::class);
        $reader->expects('listWorksheetInfo')
            ->with($path)
            ->andReturn([['totalRows' => $totalRows, 'totalColumns' => 1]]);
        $reader->expects('setReadFilter')
            ->with(Mockery::type(SpreadsheetTableReadFilter::class))
            ->andReturnSelf();
        $reader->expects('load')
            ->with($path)
            ->andReturn($spreadsheet);

        $spreadsheetReaderFactory = Mockery::mock(SpreadsheetReaderFactory::class);
        $spreadsheetReaderFactory->expects('create')
            ->with(FileExtension::XLSX)
            ->andReturn($reader);

        $spreadsheetTableFactory = new SpreadsheetTableFactory($spreadsheetReaderFactory, $maxRows, $this->getFaker()->numberBetween(10, 100));

        self::assertEquals(
            new SpreadsheetTableResult(new SpreadsheetTable([$header], [[$value]]), true),
            $spreadsheetTableFactory->create($path, FileExtension::XLSX),
        );
    }

    public function testCreateWhenTooManyColumns(): void
    {
        $path = $this->getFaker()->word();
        $header = $this->getFaker()->unique()->word();
        $value = $this->getFaker()->unique()->word();
        $maxColumns = $this->getFaker()->numberBetween(1, 10);
        $totalColumns = $this->getFaker()->numberBetween(11, 20);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            [$header],
            [$value],
        ]);

        $reader = Mockery::mock(BaseReader::class);
        $reader->expects('listWorksheetInfo')
            ->with($path)
            ->andReturn([['totalRows' => 2, 'totalColumns' => $totalColumns]]);
        $reader->expects('setReadFilter')
            ->with(Mockery::type(SpreadsheetTableReadFilter::class))
            ->andReturnSelf();
        $reader->expects('load')
            ->with($path)
            ->andReturn($spreadsheet);

        $spreadsheetReaderFactory = Mockery::mock(SpreadsheetReaderFactory::class);
        $spreadsheetReaderFactory->expects('create')
            ->with(FileExtension::XLSX)
            ->andReturn($reader);

        $spreadsheetTableFactory = new SpreadsheetTableFactory(
            $spreadsheetReaderFactory,
            $this->getFaker()->numberBetween(10, 100),
            $maxColumns,
        );

        self::assertEquals(
            new SpreadsheetTableResult(new SpreadsheetTable([$header], [[$value]]), true),
            $spreadsheetTableFactory->create($path, FileExtension::XLSX),
        );
    }

    public function testCreateFailsWhenMultipleWorksheets(): void
    {
        $path = $this->getFaker()->word();

        $reader = Mockery::mock(BaseReader::class);
        $reader->expects('listWorksheetInfo')
            ->with($path)
            ->andReturn([
                ['totalRows' => 1, 'totalColumns' => 1],
                ['totalRows' => 1, 'totalColumns' => 1],
            ]);

        $spreadsheetReaderFactory = Mockery::mock(SpreadsheetReaderFactory::class);
        $spreadsheetReaderFactory->expects('create')
            ->with(FileExtension::XLSX)
            ->andReturn($reader);

        $spreadsheetTableFactory = new SpreadsheetTableFactory(
            $spreadsheetReaderFactory,
            $this->getFaker()->numberBetween(10, 100),
            $this->getFaker()->numberBetween(10, 100),
        );

        $this->expectExceptionObject(SpreadsheetTableException::forMultipleWorksheets());

        $spreadsheetTableFactory->create($path, FileExtension::XLSX);
    }

    public function testCreateFialsWhenHeaderCell(): void
    {
        $path = $this->getFaker()->word();
        $header = $this->getFaker()->unique()->word();
        $value1 = $this->getFaker()->unique()->word();
        $value2 = $this->getFaker()->unique()->word();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            [$header, null],
            [$value1, $value2],
        ]);

        $reader = Mockery::mock(BaseReader::class);
        $reader->expects('listWorksheetInfo')
            ->with($path)
            ->andReturn([['totalRows' => 2, 'totalColumns' => 2]]);
        $reader->expects('setReadFilter')
            ->with(Mockery::type(SpreadsheetTableReadFilter::class))
            ->andReturnSelf();
        $reader->expects('load')
            ->with($path)
            ->andReturn($spreadsheet);

        $spreadsheetReaderFactory = Mockery::mock(SpreadsheetReaderFactory::class);
        $spreadsheetReaderFactory->expects('create')
            ->with(FileExtension::XLSX)
            ->andReturn($reader);

        $spreadsheetTableFactory = new SpreadsheetTableFactory(
            $spreadsheetReaderFactory,
            $this->getFaker()->numberBetween(10, 100),
            $this->getFaker()->numberBetween(10, 100),
        );

        $this->expectExceptionObject(SpreadsheetTableException::forMissingHeaderRow());

        $spreadsheetTableFactory->create($path, FileExtension::XLSX);
    }

    public function testCreateFailsWhenEmptyWorksheet(): void
    {
        $path = $this->getFaker()->word();

        $reader = Mockery::mock(BaseReader::class);
        $reader->expects('listWorksheetInfo')
            ->with($path)
            ->andReturn([['totalRows' => 0, 'totalColumns' => 0]]);
        $reader->expects('setReadFilter')
            ->with(Mockery::type(SpreadsheetTableReadFilter::class))
            ->andReturnSelf();
        $reader->expects('load')
            ->with($path)
            ->andReturn(new Spreadsheet());

        $spreadsheetReaderFactory = Mockery::mock(SpreadsheetReaderFactory::class);
        $spreadsheetReaderFactory->expects('create')
            ->with(FileExtension::XLSX)
            ->andReturn($reader);

        $spreadsheetTableFactory = new SpreadsheetTableFactory(
            $spreadsheetReaderFactory,
            $this->getFaker()->numberBetween(10, 100),
            $this->getFaker()->numberBetween(10, 100),
        );

        $this->expectExceptionObject(SpreadsheetTableException::forMissingHeaderRow());

        $spreadsheetTableFactory->create($path, FileExtension::XLSX);
    }

    public function testCreateFailsWhenFileUnreadable(): void
    {
        $path = $this->getFaker()->word();

        $reader = Mockery::mock(BaseReader::class);
        $reader->expects('listWorksheetInfo')
            ->with($path)
            ->andThrow(new ReaderException($this->getFaker()->sentence()));

        $spreadsheetReaderFactory = Mockery::mock(SpreadsheetReaderFactory::class);
        $spreadsheetReaderFactory->expects('create')
            ->with(FileExtension::XLSX)
            ->andReturn($reader);

        $spreadsheetTableFactory = new SpreadsheetTableFactory(
            $spreadsheetReaderFactory,
            $this->getFaker()->numberBetween(10, 100),
            $this->getFaker()->numberBetween(10, 100),
        );

        $this->expectExceptionObject(SpreadsheetTableException::forUnreadableFile());

        $spreadsheetTableFactory->create($path, FileExtension::XLSX);
    }

    public function testSupports(): void
    {
        $spreadsheetTableFactory = new SpreadsheetTableFactory(
            Mockery::mock(SpreadsheetReaderFactory::class),
            $this->getFaker()->numberBetween(10, 100),
            $this->getFaker()->numberBetween(10, 100),
        );

        self::assertTrue($spreadsheetTableFactory->supports(FileExtension::XLS));
    }
}
