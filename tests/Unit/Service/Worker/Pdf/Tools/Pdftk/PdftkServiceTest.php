<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf\Tools\Pdftk;

use GuzzleHttp\Psr7\Utils;
use Mockery;
use Shared\Service\ProcessFactory;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkRuntimeException;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Process\Process;

final class PdftkServiceTest extends UnitTestCase
{
    public function testExtractPage(): void
    {
        $source = Utils::tryFopen('php://memory', 'r');
        $pageNr = $this->getFaker()->numberBetween(1, 100);
        $pageContents = $this->getFaker()->sha256();
        $exitCode = 0;

        $process = Mockery::mock(Process::class);
        $process->expects('setInput')->with($source)->andReturnSelf();
        $process->expects('run')->andReturn($exitCode);
        $process->expects('isSuccessful')->twice()->andReturn(true);
        $process->expects('getOutput')->andReturn($pageContents);

        $processFactory = Mockery::mock(ProcessFactory::class);
        $processFactory->expects('create')->andReturn($process);

        $service = new PdftkService($processFactory);
        $result = $service->extractPage($source, $pageNr);

        $expected = [PdftkService::PDFTK_PATH, PdftkService::INPUT_FROM_STDIN, 'cat', (string) $pageNr, 'output', PdftkService::OUTPUT_TO_STDOUT];
        $this->assertSame($expected, $result->params);

        $this->assertSame($exitCode, $result->exitCode);
        $this->assertNull($result->errorMessage);
        $this->assertSame($pageNr, $result->pageNr);
        $this->assertSame($pageContents, $result->pageContents);
    }

    public function testExtractPageWhenItFails(): void
    {
        $source = Utils::tryFopen('php://memory', 'r');
        $pageNr = $this->getFaker()->numberBetween(1, 100);
        $errorMessage = $this->getFaker()->sentence();

        $process = Mockery::mock(Process::class);
        $process->expects('setInput')->with($source)->andReturnSelf();
        $process->expects('run')->andReturn($exitCode = 1);
        $process->expects('isSuccessful')->twice()->andReturn(false);
        $process->expects('getErrorOutput')->andReturn($errorMessage);

        $processFactory = Mockery::mock(ProcessFactory::class);
        $processFactory->expects('create')->andReturn($process);

        $service = new PdftkService($processFactory);
        $result = $service->extractPage($source, $pageNr);

        $this->assertSame($exitCode, $result->exitCode);
        $this->assertSame($errorMessage, $result->errorMessage);
        $this->assertNull($result->pageContents);
    }

    public function testExtractNumberOfPages(): void
    {
        $source = Utils::tryFopen('php://memory', 'r');

        $process = Mockery::mock(Process::class);
        $process->expects('setInput')->with($source)->andReturnSelf();
        $process->expects('run')->andReturn($exitCode = 0);
        $process->expects('isSuccessful')->times(2)->andReturn(true);
        $process->expects('getOutput')->andReturn($this->getMockDumpDataOutput($numberOfPages = 4));

        $processFactory = Mockery::mock(ProcessFactory::class);
        $processFactory->expects('create')->andReturn($process);

        $service = new PdftkService($processFactory);
        $result = $service->extractNumberOfPages($source);

        $this->assertSame([PdftkService::PDFTK_PATH, PdftkService::INPUT_FROM_STDIN, 'dump_data'], $result->params);
        $this->assertSame($exitCode, $result->exitCode);
        $this->assertNull($result->errorMessage);
        $this->assertSame($numberOfPages, $result->numberOfPages);
    }

    public function testExtractNumberOfPagesWithInvalidOutput(): void
    {
        $source = Utils::tryFopen('php://memory', 'r');

        $process = Mockery::mock(Process::class);
        $process->expects('setInput')->with($source)->andReturnSelf();
        $process->expects('run')->andReturn(0);
        $process->expects('isSuccessful')->andReturn(true);
        $process->expects('getOutput')->andReturn('Lorem ipsum yada yada yada');

        $this->expectExceptionObject(PdftkRuntimeException::noPageCountResultFound());

        $processFactory = Mockery::mock(ProcessFactory::class);
        $processFactory->expects('create')->andReturn($process);

        $service = new PdftkService($processFactory);
        $service->extractNumberOfPages($source);
    }

    private function getMockDumpDataOutput(string|int $numberOfPages): string
    {
        return <<<"EOT"
            InfoBegin
            InfoKey: Producer
            InfoValue: ZyLAB PDF export module
            PdfID0: a7ef8e246808e41322b0aac274b38246e38a5930
            PdfID1: a7ef8e246808e41322b0aac274b38246e38a5930
            NumberOfPages: $numberOfPages
            PageMediaBegin
            PageMediaNumber: 1
            PageMediaRotation: 0
            PageMediaRect: 0 0 595.44 841.68
            PageMediaDimensions: 595.44 841.68
            PageMediaBegin
            PageMediaNumber: 2
            PageMediaRotation: 0
            PageMediaRect: 0 0 595.44 841.68
            PageMediaDimensions: 595.44 841.68
            PageMediaBegin
            PageMediaNumber: 3
            PageMediaRotation: 0
            PageMediaRect: 0 0 595.44 841.68
            PageMediaDimensions: 595.44 841.68
            PageMediaBegin
            PageMediaNumber: 4
            PageMediaRotation: 0
            PageMediaRect: 0 0 595.44 841.68
            PageMediaDimensions: 595.44 841.68
            EOT;
    }
}
