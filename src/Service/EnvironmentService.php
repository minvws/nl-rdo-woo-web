<?php

declare(strict_types=1);

namespace Shared\Service;

use Symfony\Component\HttpKernel\KernelInterface;

class EnvironmentService
{
    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    public function isDev(): bool
    {
        return $this->kernel->getEnvironment() === 'dev';
    }
}
