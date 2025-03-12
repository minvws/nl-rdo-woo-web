<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload\Command;

use Symfony\Component\Uid\Uuid;

class GenerateBatchDownloadCommand
{
    public function __construct(
        public Uuid $uuid,
    ) {
    }
}
