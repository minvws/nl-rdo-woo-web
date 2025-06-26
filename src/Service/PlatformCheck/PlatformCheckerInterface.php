<?php

declare(strict_types=1);

namespace App\Service\PlatformCheck;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.platform_checker')]
interface PlatformCheckerInterface
{
    /**
     * @return PlatformCheckResult[]
     */
    public function getResults(): array;
}
