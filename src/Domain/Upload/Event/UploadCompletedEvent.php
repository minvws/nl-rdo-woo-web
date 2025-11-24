<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Event;

use Shared\Domain\Upload\UploadEntity;

readonly class UploadCompletedEvent
{
    public function __construct(
        public UploadEntity $uploadEntity,
    ) {
    }
}
