<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content\Extractor\Tesseract;

use GuzzleHttp\Psr7\Utils;
use Mockery;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\Content\Extractor\Tesseract\TesseractService;
use Shared\Service\ProcessFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Process\Process;

final class TesseractServiceTest extends UnitTestCase
{
    public function testExtract(): void
    {
        $source = Utils::tryFopen('php://memory', 'r');
        $png = $this->getFaker()->sha256();
        $tesseractOutput = $this->getFaker()->sentence();

        $pdftoppmProcess = Mockery::mock(Process::class);
        $pdftoppmProcess->expects('setInput')->with($source)->andReturnSelf();
        $pdftoppmProcess->expects('setTimeout')->with(120)->andReturnSelf();
        $pdftoppmProcess->expects('run');
        $pdftoppmProcess->expects('isSuccessful')->andReturnTrue();
        $pdftoppmProcess->expects('getOutput')->andReturn($png);

        $tesseractProcess = Mockery::mock(Process::class);
        $tesseractProcess->expects('setInput')->with($png)->andReturnSelf();
        $tesseractProcess->expects('setTimeout')->with(120)->andReturnSelf();
        $tesseractProcess->expects('run');
        $tesseractProcess->expects('isSuccessful')->andReturnTrue();
        $tesseractProcess->expects('getOutput')->andReturn($tesseractOutput);

        $processFactory = Mockery::mock(ProcessFactory::class);
        $processFactory
            ->expects('create')
            ->with([TesseractService::PDFTOPPM_PATH, '-png', '-r', '300', '-singlefile', TesseractService::PDFTOPPM_INPUT_FROM_STDIN])
            ->andReturn($pdftoppmProcess);
        $processFactory
            ->expects('create')
            ->with([TesseractService::TESSERACT_PATH, TesseractService::TESSERACT_INPUT_FROM_STDIN, 'stdout'])
            ->andReturn($tesseractProcess);

        $tesseract = new TesseractService(Mockery::mock(LoggerInterface::class), $processFactory);

        self::assertSame($tesseractOutput, $tesseract->extract($source));
    }

    public function testExtractWhenPdftoppmFails(): void
    {
        $source = Utils::tryFopen('php://memory', 'r');
        $errorOutput = $this->getFaker()->sentence();

        $pdftoppmProcess = Mockery::mock(Process::class);
        $pdftoppmProcess->expects('setInput')->with($source)->andReturnSelf();
        $pdftoppmProcess->expects('setTimeout')->with(120)->andReturnSelf();
        $pdftoppmProcess->expects('run');
        $pdftoppmProcess->expects('isSuccessful')->andReturnFalse();
        $pdftoppmProcess->expects('getErrorOutput')->andReturn($errorOutput);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('error')->with('pdftoppm failed', ['error_output' => $errorOutput]);

        $processFactory = Mockery::mock(ProcessFactory::class);
        $processFactory
            ->expects('create')
            ->with([TesseractService::PDFTOPPM_PATH, '-png', '-r', '300', '-singlefile', TesseractService::PDFTOPPM_INPUT_FROM_STDIN])
            ->andReturn($pdftoppmProcess);

        $tesseract = new TesseractService($logger, $processFactory);

        self::assertSame('', $tesseract->extract($source));
    }

    public function testExtractWhenTesseractFails(): void
    {
        $source = Utils::tryFopen('php://memory', 'r');
        $png = $this->getFaker()->sha256();
        $errorOutput = $this->getFaker()->sentence();

        $pdftoppmProcess = Mockery::mock(Process::class);
        $pdftoppmProcess->expects('setInput')->with($source)->andReturnSelf();
        $pdftoppmProcess->expects('setTimeout')->with(120)->andReturnSelf();
        $pdftoppmProcess->expects('run');
        $pdftoppmProcess->expects('isSuccessful')->andReturnTrue();
        $pdftoppmProcess->expects('getOutput')->andReturn($png);

        $tesseractProcess = Mockery::mock(Process::class);
        $tesseractProcess->expects('setInput')->with($png)->andReturnSelf();
        $tesseractProcess->expects('setTimeout')->with(120)->andReturnSelf();
        $tesseractProcess->expects('run');
        $tesseractProcess->expects('isSuccessful')->andReturnFalse();
        $tesseractProcess->expects('getErrorOutput')->andReturn($errorOutput);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('error')->with('Tesseract failed', ['error_output' => $errorOutput]);

        $processFactory = Mockery::mock(ProcessFactory::class);
        $processFactory
            ->expects('create')
            ->with([TesseractService::PDFTOPPM_PATH, '-png', '-r', '300', '-singlefile', TesseractService::PDFTOPPM_INPUT_FROM_STDIN])
            ->andReturn($pdftoppmProcess);
        $processFactory
            ->expects('create')
            ->with([TesseractService::TESSERACT_PATH, TesseractService::TESSERACT_INPUT_FROM_STDIN, 'stdout'])
            ->andReturn($tesseractProcess);

        $tesseract = new TesseractService($logger, $processFactory);

        self::assertSame('', $tesseract->extract($source));
    }
}
