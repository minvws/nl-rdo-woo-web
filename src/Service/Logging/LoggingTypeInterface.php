<?php

declare(strict_types=1);

namespace App\Service\Logging;

interface LoggingTypeInterface
{
    public function disable(): void;

    public function isDisabled(): bool;

    public function restore(): void;
}
