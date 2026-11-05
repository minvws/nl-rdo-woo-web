<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf\Tools\Pdftoppm;

use Mockery;
use Shared\Service\ProcessFactory;
use Shared\Service\Worker\Pdf\Tools\Pdftoppm\PdftoppmService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Process\Process;

final class PdftoppmServiceTest extends UnitTestCase
{
    public function testCreateThumbnail(): void
    {
        $pdf = $this->getFaker()->sha256();
        $thumbnail = $this->getFaker()->sha256();

        $process = Mockery::mock(Process::class);
        $process->expects('setInput')->with($pdf)->andReturnSelf();
        $process->expects('run')->andReturn($exitCode = 0);
        $process->expects('isSuccessful')->twice()->andReturnTrue();
        $process->expects('getOutput')->andReturn($thumbnail);

        $processFactory = Mockery::mock(ProcessFactory::class);
        $processFactory->expects('create')->andReturn($process);

        $result = new PdftoppmService($processFactory)->createThumbnail($pdf);

        $this->assertSame(
            [
                PdftoppmService::PDFTOPPM_PATH,
                '-png',
                '-scale-to',
                '200',
                '-r',
                '150',
                '-singlefile',
                PdftoppmService::INPUT_FROM_STDIN,
            ],
            $result->params,
        );
        $this->assertSame($exitCode, $result->exitCode);
        $this->assertNull($result->errorMessage);
        $this->assertSame($thumbnail, $result->thumbnail);
    }

    public function testCreateThumbnailWhenItFails(): void
    {
        $pdf = $this->getFaker()->sha256();
        $errorMessage = $this->getFaker()->sentence();

        $process = Mockery::mock(Process::class);
        $process->expects('setInput')->with($pdf)->andReturnSelf();
        $process->expects('run')->andReturn($exitCode = 1);
        $process->expects('isSuccessful')->twice()->andReturnFalse();
        $process->expects('getErrorOutput')->andReturn($errorMessage);

        $processFactory = Mockery::mock(ProcessFactory::class);
        $processFactory->expects('create')->andReturn($process);

        $result = new PdftoppmService($processFactory)->createThumbnail($pdf);

        $this->assertSame(
            [
                PdftoppmService::PDFTOPPM_PATH,
                '-png',
                '-scale-to',
                '200',
                '-r',
                '150',
                '-singlefile',
                PdftoppmService::INPUT_FROM_STDIN,
            ],
            $result->params,
        );
        $this->assertSame($exitCode, $result->exitCode);
        $this->assertSame($errorMessage, $result->errorMessage);
        $this->assertNull($result->thumbnail);
    }
}
