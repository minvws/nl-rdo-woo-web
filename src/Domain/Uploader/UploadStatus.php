<?php

declare(strict_types=1);

namespace App\Domain\Uploader;

enum UploadStatus: string
{
    case INCOMPLETE = 'incomplete';
    case UPLOADED = 'uploaded';
    case VALIDATION_FAILED = 'validation_failed';
    case VALIDATION_PASSED = 'validation_passed';
    case STORED = 'stored';
    case ABORTED = 'aborted';

    public function isIncomplete(): bool
    {
        return $this === self::INCOMPLETE;
    }

    public function isImmutable(): bool
    {
        return $this === self::VALIDATION_FAILED
            || $this === self::ABORTED
            || $this === self::STORED;
    }

    public function isDownloadable(): bool
    {
        return $this === self::UPLOADED
            || $this === self::VALIDATION_PASSED;
    }

    public function isUploaded(): bool
    {
        return $this === self::UPLOADED;
    }
}
