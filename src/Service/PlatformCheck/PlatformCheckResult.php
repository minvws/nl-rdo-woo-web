<?php

declare(strict_types=1);

namespace Shared\Service\PlatformCheck;

class PlatformCheckResult
{
    private function __construct(
        public string $description,
        public bool $successful,
        public string $output,
    ) {
    }

    public static function error(string $description, string $output): self
    {
        return new self($description, false, $output);
    }

    public static function success(string $description): self
    {
        return new self($description, true, 'ok');
    }
}
