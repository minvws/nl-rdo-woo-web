<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content\Extractor\Tesseract;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\Content\Extractor\Tesseract\TesseractService;
use Shared\Service\Storage\LocalFilesystem;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Process\Process;

final class TesseractServiceTest extends UnitTestCase
{
    protected LoggerInterface&MockInterface $logger;
    protected LocalFilesystem&MockInterface $localFilesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->localFilesystem = Mockery::mock(LocalFilesystem::class);
    }

    public function testExtract(): void
    {
        $this->localFilesystem
            ->expects('createTempDir')
            ->andReturn($tempDir = 'tempDir');

        $targetPngPath = $tempDir . '/page';

        $tesseract = $this->getTesseract();

        $tesseract
            ->expects('getNewProcess')
            ->with([TesseractService::PDFTOPPM_PATH, '-png', '-r', '300', '-singlefile', $sourcePdfPath = 'sourcePdfPath', $targetPngPath])
            ->andReturn($pdftoppmProcess = $this->getProcess());

        $pdftoppmProcess
            ->expects('isSuccessful')
            ->andReturnTrue();

        $tesseract
            ->expects('getNewProcess')
            ->with([TesseractService::TESSERACT_PATH, $targetPngPath . '.png', 'stdout'])
            ->andReturn($tesseractProcess = $this->getProcess());

        $tesseractProcess
            ->expects('isSuccessful')
            ->andReturnTrue();

        $this->localFilesystem
            ->expects('deleteDirectory')
            ->with($tempDir);

        $tesseractProcess
            ->expects('getOutput')
            ->andReturn($tesseractOutput = 'output');

        $result = $tesseract->extract($sourcePdfPath);

        $this->assertSame($tesseractOutput, $result);
    }

    public function testExtractWhenFailedToCreateTempDir(): void
    {
        $this->localFilesystem
            ->expects('createTempDir')
            ->andReturnFalse();

        $tesseract = $this->getTesseract();

        $tesseract->shouldNotReceive('getNewProcess');

        $result = $tesseract->extract('sourcePdfPath');

        $this->assertSame('', $result);
    }

    public function testExtractWhenPdftoppmFails(): void
    {
        $this->localFilesystem
            ->expects('createTempDir')
            ->andReturn($tempDir = 'tempDir');

        $targetPngPath = $tempDir . '/page';

        $tesseract = $this->getTesseract();

        $tesseract
            ->expects('getNewProcess')
            ->with([TesseractService::PDFTOPPM_PATH, '-png', '-r', '300', '-singlefile', $sourcePdfPath = 'sourcePdfPath', $targetPngPath])
            ->andReturn($pdftoppmProcess = $this->getProcess());

        $pdftoppmProcess
            ->expects('isSuccessful')
            ->andReturnFalse();

        $pdftoppmProcess
            ->expects('getErrorOutput')
            ->andReturn($pdftoppmProcessErrorOutput = 'error_output');

        $this->logger
            ->expects('error')
            ->with('pdftoppm failed', [
                'sourcePath' => $sourcePdfPath,
                'targetPngPath' => $targetPngPath . '.png',
                'error_output' => $pdftoppmProcessErrorOutput,
            ]);

        $this->localFilesystem
            ->expects('deleteDirectory')
            ->with($tempDir);

        $result = $tesseract->extract($sourcePdfPath);

        $this->assertSame('', $result);
    }

    public function testExtractWhenTesseractFails(): void
    {
        $this->localFilesystem
            ->expects('createTempDir')
            ->andReturn($tempDir = 'tempDir');

        $targetPngPath = $tempDir . '/page';

        $tesseract = $this->getTesseract();

        $tesseract
            ->expects('getNewProcess')
            ->with([TesseractService::PDFTOPPM_PATH, '-png', '-r', '300', '-singlefile', $sourcePdfPath = 'sourcePdfPath', $targetPngPath])
            ->andReturn($pdftoppmProcess = $this->getProcess());

        $pdftoppmProcess
            ->expects('isSuccessful')
            ->andReturnTrue();

        $tesseract
            ->expects('getNewProcess')
            ->with([TesseractService::TESSERACT_PATH, $targetPngPath . '.png', 'stdout'])
            ->andReturn($tesseractProcess = $this->getProcess());

        $tesseractProcess
            ->expects('isSuccessful')
            ->andReturnFalse();

        $tesseractProcess
            ->expects('getErrorOutput')
            ->andReturn($tesseractProcessErrorOutput = 'error_output');

        $this->logger
            ->expects('error')
            ->with('Tesseract failed', [
                'sourcePath' => $sourcePdfPath,
                'targetPngPath' => $targetPngPath . '.png',
                'error_output' => $tesseractProcessErrorOutput,
            ]);

        $this->localFilesystem
            ->expects('deleteDirectory')
            ->with($tempDir);

        $result = $tesseract->extract($sourcePdfPath);

        $this->assertSame('', $result);
    }

    private function getTesseract(): TesseractService&MockInterface
    {
        $tesseract = Mockery::mock(TesseractService::class, [$this->logger, $this->localFilesystem])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        return $tesseract;
    }

    private function getProcess(): Process&MockInterface
    {
        $process = Mockery::mock(Process::class);
        $process->expects('setTimeout')->with(120);
        $process->expects('run');

        return $process;
    }
}
