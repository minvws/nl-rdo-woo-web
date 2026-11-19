<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\FileReader;

use GuzzleHttp\Psr7\Utils;
use Mockery;
use RuntimeException;
use Shared\Service\FileReader\ColumnMapping;
use Shared\Service\FileReader\ExcelCsvReaderFactory;
use Shared\Service\ProcessFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Process\Process;

use function implode;
use function sprintf;

use const PHP_EOL;

final class ExcelCsvReaderFactoryTest extends UnitTestCase
{
    public function testCreateReader(): void
    {
        $source = Utils::tryFopen('php://memory', 'r');
        $columnName = $this->getFaker()->word();
        $value = $this->getFaker()->word();

        $process = Mockery::mock(Process::class);
        $process->expects('setInput')->with($source)->andReturnSelf();
        $process->expects('run')->andReturn(0);
        $process->expects('isSuccessful')->andReturnTrue();
        $process->expects('getOutput')->andReturn(implode(PHP_EOL, [$columnName, $value]));

        $processFactory = Mockery::mock(ProcessFactory::class);
        $processFactory->expects('create')->with(['/usr/bin/xlsx2csv', '-'])->andReturn($process);

        $reader = new ExcelCsvReaderFactory($processFactory)->createReader(
            $source,
            new ColumnMapping($columnName, true, [$columnName]),
        );

        self::assertSame(1, $reader->getCount());
        self::assertSame($value, $reader->getString(1, $columnName));
    }

    public function testCreateReaderThrowsExceptionWhenConversionFails(): void
    {
        $source = Utils::tryFopen('php://memory', 'r');
        $errorOutput = $this->getFaker()->sentence();

        $process = Mockery::mock(Process::class);
        $process->expects('setInput')->with($source)->andReturnSelf();
        $process->expects('run')->andReturn(1);
        $process->expects('isSuccessful')->andReturnFalse();
        $process->expects('getErrorOutput')->andReturn($errorOutput);

        $processFactory = Mockery::mock(ProcessFactory::class);
        $processFactory->expects('create')->with(['/usr/bin/xlsx2csv', '-'])->andReturn($process);

        $this->expectExceptionObject(new RuntimeException(sprintf('Failed to create csv: %s', $errorOutput)));

        new ExcelCsvReaderFactory($processFactory)->createReader($source);
    }
}
