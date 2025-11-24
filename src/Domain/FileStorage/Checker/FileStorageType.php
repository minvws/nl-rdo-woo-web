<?php

declare(strict_types=1);

namespace Shared\Domain\FileStorage\Checker;

enum FileStorageType: string
{
    case DOCUMENT = 'document';
    case BATCH = 'batch';
}
