<?php

declare(strict_types=1);

namespace App\Domain\Uploader\Event;

use App\Domain\Uploader\UploadEntity;

readonly class UploadCompletedEvent
{
    public function __construct(
        public UploadEntity $uploadEntity,
    ) {
    }
}
