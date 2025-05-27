<?php

declare(strict_types=1);

namespace App\Domain\FileStorage\Checker;

enum FileStorageType: string
{
    case DOCUMENT = 'document';
    case BATCH = 'batch';
}
