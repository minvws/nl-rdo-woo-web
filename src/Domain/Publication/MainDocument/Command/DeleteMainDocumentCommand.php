<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument\Command;

use Symfony\Component\Uid\Uuid;

readonly class DeleteMainDocumentCommand
{
    public function __construct(
        public Uuid $dossierId,
    ) {
    }
}
