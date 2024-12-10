<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Enum;

enum DocumentFileUploadStatus: string
{
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
}
