<?php

declare(strict_types=1);

namespace Shared\Service\PlatformCheck;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.platform_checker')]
interface PlatformCheckerInterface
{
    /**
     * @return array<array-key, PlatformCheckResult>
     */
    public function getResults(): array;
}
