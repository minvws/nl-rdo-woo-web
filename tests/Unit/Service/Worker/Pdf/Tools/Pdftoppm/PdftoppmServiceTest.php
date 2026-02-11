<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf\Tools\Pdftoppm;

use Mockery;
use Mockery\MockInterface;
use Shared\Service\Worker\Pdf\Tools\Pdftoppm\PdftoppmService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Process\Process;

final class PdftoppmServiceTest extends UnitTestCase
{
    protected Process&MockInterface $process;

    protected function setUp(): void
    {
        parent::setUp();

        $this->process = Mockery::mock(Process::class);
    }

    public function testCreateThumbnail(): void
    {
        $this->process
            ->shouldReceive('run')
            ->once()
            ->andReturn($exitCode = 0);

        $this->process
            ->shouldReceive('isSuccessful')
            ->once()
            ->andReturnTrue();

        $result = $this->getService()->createThumbnail($sourcePdf = 'sourcePdf', $targetPath = 'targetPath');

        $this->assertSame(
            [
                '/usr/bin/pdftoppm',
                '-png',
                '-scale-to',
                '200',
                '-r',
                '150',
                '-singlefile',
                $sourcePdf,
                $targetPath,
            ],
            $result->params,
        );
        $this->assertSame($exitCode, $result->exitCode);
        $this->assertNull($result->errorMessage);
        $this->assertSame($sourcePdf, $result->sourcePdf);
        $this->assertSame($targetPath, $result->targetPath);
    }

    public function testCreateThumbnailWhenItFails(): void
    {
        $this->process
            ->shouldReceive('run')
            ->once()
            ->andReturn($exitCode = 1);

        $this->process
            ->shouldReceive('isSuccessful')
            ->once()
            ->andReturnFalse();

        $this->process
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn($errorMessage = 'errorMessage');

        $result = $this->getService()->createThumbnail($sourcePdf = 'sourcePdf', $targetPath = 'targetPath');

        $this->assertSame(
            [
                '/usr/bin/pdftoppm',
                '-png',
                '-scale-to',
                '200',
                '-r',
                '150',
                '-singlefile',
                $sourcePdf,
                $targetPath,
            ],
            $result->params,
        );
        $this->assertSame($exitCode, $result->exitCode);
        $this->assertSame($errorMessage, $result->errorMessage);
        $this->assertSame($sourcePdf, $result->sourcePdf);
        $this->assertSame($targetPath, $result->targetPath);
    }

    private function getService(): PdftoppmService&MockInterface
    {
        /** @var PdftoppmService&MockInterface $instance */
        $instance = Mockery::mock(PdftoppmService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $instance
            ->shouldReceive('getNewProcess')
            ->andReturn($this->process);

        return $instance;
    }
}
