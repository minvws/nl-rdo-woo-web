<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker\Pdf\Tools;

use App\Service\Storage\LocalFilesystem;
use App\Service\Worker\Pdf\Tools\TesseractService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

final class TesseractServiceTest extends UnitTestCase
{
    protected LoggerInterface&MockInterface $logger;
    protected LocalFilesystem&MockInterface $localFilesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->localFilesystem = \Mockery::mock(LocalFilesystem::class);
    }

    public function testExtract(): void
    {
        $this->localFilesystem
            ->shouldReceive('createTempDir')
            ->andReturn($tempDir = 'tempDir');

        $targetPngPath = $tempDir . '/page';

        $tesseract = $this->getTesseract();

        $tesseract
            ->shouldReceive('getNewProcess')
            ->with([TesseractService::PDFTOPPM_PATH, '-png', '-r', '300', '-singlefile', $sourcePdfPath = 'sourcePdfPath', $targetPngPath])
            ->andReturn($pdftoppmProcess = $this->getProcess());

        $pdftoppmProcess
            ->shouldReceive('isSuccessful')
            ->andReturnTrue();

        $tesseract
            ->shouldReceive('getNewProcess')
            ->with([TesseractService::TESSERACT_PATH, $targetPngPath . '.png', 'stdout'])
            ->andReturn($tesseractProcess = $this->getProcess());

        $tesseractProcess
            ->shouldReceive('isSuccessful')
            ->andReturnTrue();

        $this->localFilesystem
            ->shouldReceive('deleteDirectory')
            ->with($tempDir);

        $tesseractProcess
            ->shouldReceive('getOutput')
            ->andReturn($tesseractOutput = 'output');

        $result = $tesseract->extract($sourcePdfPath);

        $this->assertSame($tesseractOutput, $result);
    }

    public function testExtractWhenFailedToCreateTempDir(): void
    {
        $this->localFilesystem
            ->shouldReceive('createTempDir')
            ->andReturnFalse();

        $tesseract = $this->getTesseract();

        $tesseract->shouldNotReceive('getNewProcess');

        $result = $tesseract->extract('sourcePdfPath');

        $this->assertSame('', $result);
    }

    public function testExtractWhenPdftoppmFails(): void
    {
        $this->localFilesystem
            ->shouldReceive('createTempDir')
            ->andReturn($tempDir = 'tempDir');

        $targetPngPath = $tempDir . '/page';

        $tesseract = $this->getTesseract();

        $tesseract
            ->shouldReceive('getNewProcess')
            ->with([TesseractService::PDFTOPPM_PATH, '-png', '-r', '300', '-singlefile', $sourcePdfPath = 'sourcePdfPath', $targetPngPath])
            ->andReturn($pdftoppmProcess = $this->getProcess());

        $pdftoppmProcess
            ->shouldReceive('isSuccessful')
            ->andReturnFalse();

        $pdftoppmProcess
            ->shouldReceive('getErrorOutput')
            ->andReturn($pdftoppmProcessErrorOutput = 'error_output');

        $this->logger
            ->shouldReceive('error')
            ->with('pdftoppm failed', [
                'sourcePath' => $sourcePdfPath,
                'targetPngPath' => $targetPngPath . '.png',
                'error_output' => $pdftoppmProcessErrorOutput,
            ]);

        $this->localFilesystem
            ->shouldReceive('deleteDirectory')
            ->with($tempDir);

        $result = $tesseract->extract($sourcePdfPath);

        $this->assertSame('', $result);
    }

    public function testExtractWhenTesseractFails(): void
    {
        $this->localFilesystem
            ->shouldReceive('createTempDir')
            ->andReturn($tempDir = 'tempDir');

        $targetPngPath = $tempDir . '/page';

        $tesseract = $this->getTesseract();

        $tesseract
            ->shouldReceive('getNewProcess')
            ->with([TesseractService::PDFTOPPM_PATH, '-png', '-r', '300', '-singlefile', $sourcePdfPath = 'sourcePdfPath', $targetPngPath])
            ->andReturn($pdftoppmProcess = $this->getProcess());

        $pdftoppmProcess
            ->shouldReceive('isSuccessful')
            ->andReturnTrue();

        $tesseract
            ->shouldReceive('getNewProcess')
            ->with([TesseractService::TESSERACT_PATH, $targetPngPath . '.png', 'stdout'])
            ->andReturn($tesseractProcess = $this->getProcess());

        $tesseractProcess
            ->shouldReceive('isSuccessful')
            ->andReturnFalse();

        $tesseractProcess
            ->shouldReceive('getErrorOutput')
            ->andReturn($tesseractProcessErrorOutput = 'error_output');

        $this->logger
            ->shouldReceive('error')
            ->with('Tesseract failed', [
                'sourcePath' => $sourcePdfPath,
                'targetPngPath' => $targetPngPath . '.png',
                'error_output' => $tesseractProcessErrorOutput,
            ]);

        $this->localFilesystem
            ->shouldReceive('deleteDirectory')
            ->with($tempDir);

        $result = $tesseract->extract($sourcePdfPath);

        $this->assertSame('', $result);
    }

    private function getTesseract(): TesseractService&MockInterface
    {
        /** @var TesseractService&MockInterface $tesseract */
        $tesseract = \Mockery::mock(TesseractService::class, [$this->logger, $this->localFilesystem])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        return $tesseract;
    }

    private function getProcess(): Process&MockInterface
    {
        /** @var Process&MockInterface $process */
        $process = \Mockery::mock(Process::class);
        $process->shouldReceive('setTimeout')->with(120);
        $process->shouldReceive('run');

        return $process;
    }
}
