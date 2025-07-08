<?php

declare(strict_types=1);

namespace App\Domain\Upload\Command;

use App\Domain\Upload\UploadEntity;
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
