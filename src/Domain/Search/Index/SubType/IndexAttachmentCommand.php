<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType;

use Symfony\Component\Uid\Uuid;

final readonly class IndexAttachmentCommand
{
    public function __construct(
        public Uuid $uuid,
    ) {
    }
}
