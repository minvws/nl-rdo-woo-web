<?php

declare(strict_types=1);

namespace App\Domain\Upload\AntiVirus;

readonly class FileScannedEvent
{
    public function __construct(
        public string $path,
        public bool $hasFailed,
        public ?string $reason,
    ) {
    }
}
