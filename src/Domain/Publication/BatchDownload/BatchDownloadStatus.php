<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload;

enum BatchDownloadStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case OUTDATED = 'outdated';

    public function isNotCompleted(): bool
    {
        return $this !== self::COMPLETED;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isOutdated(): bool
    {
        return $this === self::OUTDATED;
    }

    public function isDownloadable(): bool
    {
        return $this->isCompleted() || $this->isOutdated();
    }
}
