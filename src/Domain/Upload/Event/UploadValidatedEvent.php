<?php

declare(strict_types=1);

namespace App\Domain\Upload\Event;

use App\Domain\Upload\UploadEntity;

readonly class UploadValidatedEvent
{
    public function __construct(
        public UploadEntity $uploadEntity,
    ) {
    }
}
