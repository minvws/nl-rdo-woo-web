<?php

declare(strict_types=1);

namespace Shared\Service\Logging;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.logging.type')]
interface LoggingTypeInterface
{
    public function disable(): void;

    public function isDisabled(): bool;

    public function restore(): void;
}
