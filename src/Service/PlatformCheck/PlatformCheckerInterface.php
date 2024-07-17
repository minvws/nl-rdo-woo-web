<?php

declare(strict_types=1);

namespace App\Service\PlatformCheck;

interface PlatformCheckerInterface
{
    /**
     * @return PlatformCheckResult[]
     */
    public function getResults(): array;
}
