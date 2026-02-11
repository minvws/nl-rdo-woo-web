<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Mockery;
use Mockery\MockInterface;
use Shared\Service\EnvironmentService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class EnvironmentServiceTest extends UnitTestCase
{
    private KernelInterface&MockInterface $kernel;
    private EnvironmentService $environmentService;

    protected function setUp(): void
    {
        $this->kernel = Mockery::mock(KernelInterface::class);
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
