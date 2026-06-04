<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Upload;

enum UploadStatus: string
{
    case NO_UPLOAD_REQUIRED = 'no_upload_required';
    case UPLOAD_REQUIRED = 'upload_required';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
    case PROCESSING_FAILED = 'processing_failed';

    public function isFailed(): bool
    {
        return $this === self::PROCESSING_FAILED;
    }

    public function isProcessing(): bool
    {
        return $this === self::PROCESSING;
    }
}
