<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf\Tools\Pdftk;

use Mockery;
use Mockery\MockInterface;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkRuntimeException;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Process\Process;

final class PdftkServiceTest extends UnitTestCase
{
    protected Process&MockInterface $process;

    protected function setUp(): void
    {
        parent::setUp();

        $this->process = Mockery::mock(Process::class);
    }

    public function testExtractPage(): void
    {
        $this->process
            ->shouldReceive('run')
            ->once()
            ->andReturn($exitCode = 0);

        $this->process
            ->shouldReceive('isSuccessful')
            ->andReturn(true);

        $service = $this->getService();
        $result = $service->extractPage($sourcePdf = 'sourcePdf', $pageNr = 1337, $targetPath = 'targetPath');

        $this->assertSame(['/usr/bin/pdftk', $sourcePdf, 'cat', $pageNr, 'output', $targetPath], $result->params);
        $this->assertSame($exitCode, $result->exitCode);
        $this->assertNull($result->errorMessage);
        $this->assertSame($sourcePdf, $result->sourcePdf);
        $this->assertSame($pageNr, $result->pageNr);
        $this->assertSame($targetPath, $result->targetPath);
    }

    public function testExtractNumberOfPages(): void
    {
        $this->process
            ->shouldReceive('run')
            ->once()
            ->andReturn($exitCode = 0);

        $this->process
            ->shouldReceive('isSuccessful')
            ->andReturn(true);

        $this->process
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn($this->getMockDumpDataOutput($numberOfPages = 4));

        $service = $this->getService();
        $result = $service->extractNumberOfPages($sourcePdf = 'sourcePdf');

        $this->assertSame(['/usr/bin/pdftk', $sourcePdf, 'dump_data'], $result->params);
        $this->assertSame($exitCode, $result->exitCode);
        $this->assertNull($result->errorMessage);
        $this->assertSame($sourcePdf, $result->sourcePdf);
        $this->assertSame($numberOfPages, $result->numberOfPages);
    }

    public function testExtractNumberOfPagesWithInvalidOutput(): void
    {
        $this->process
            ->shouldReceive('run')
            ->once()
            ->andReturn($exitCode = 0);

        $this->process
            ->shouldReceive('isSuccessful')
            ->andReturn(true);

        $this->process
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Lorem ipsum yada yada yada');

        $this->expectExceptionObject(PdftkRuntimeException::noPageCountResultFound());

        $service = $this->getService();
        $service->extractNumberOfPages($sourcePdf = 'sourcePdf');
    }

    private function getService(): PdftkService&MockInterface
    {
        /** @var PdftkService&MockInterface $instance */
        $instance = Mockery::mock(PdftkService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $instance
            ->shouldReceive('getNewProcess')
            ->andReturn($this->process);

        return $instance;
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
