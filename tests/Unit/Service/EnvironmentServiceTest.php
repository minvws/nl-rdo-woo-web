<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\EnvironmentService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class EnvironmentServiceTest extends UnitTestCase
{
    private KernelInterface&MockInterface $kernel;
    private EnvironmentService $environmentService;

    protected function setUp(): void
    {
        $this->kernel = \Mockery::mock(KernelInterface::class);
        $this->environmentService = new EnvironmentService($this->kernel);
    }

    public function testIsDevReturnsTrueWhenEnvironmentIsDev(): void
    {
        $this->kernel->shouldReceive('getEnvironment')->andReturn('dev', 'prod', 'something else');

        self::assertTrue($this->environmentService->isDev()); // dev
        self::assertFalse($this->environmentService->isDev()); // prod
        self::assertFalse($this->environmentService->isDev()); // something else
    }
}
