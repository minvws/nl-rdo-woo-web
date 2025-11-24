<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\AntiVirus;

enum FileScanResult: string
{
    case TECHNICAL_ERROR = 'technical error';
    case MAX_SIZE_EXCEEDED = 'max_size_exceeded';
    case UNSAFE = 'unsafe';
    case SAFE = 'safe';

    public function isNotSafe(): bool
    {
        return $this !== self::SAFE;
    }

    public function isMaxSizeExceeded(): bool
    {
        return $this === self::MAX_SIZE_EXCEEDED;
    }

    public function isTechnicalError(): bool
    {
        return $this === self::TECHNICAL_ERROR;
    }
}
