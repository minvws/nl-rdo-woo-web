<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\AntiVirus;

readonly class FileScannedEvent
{
    public function __construct(
        public string $path,
        public bool $hasFailed,
        public ?string $reason,
    ) {
    }
}
