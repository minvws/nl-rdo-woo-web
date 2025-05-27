<?php

declare(strict_types=1);

namespace App\Domain\Uploader\Command;

use App\Domain\Uploader\UploadEntity;
use Symfony\Component\Uid\Uuid;

class ValidateUploadCommand
{
    public function __construct(
        public Uuid $uuid,
    ) {
    }

    public static function forEntity(UploadEntity $uploadEntity): self
    {
        return new self($uploadEntity->getId());
    }
}
