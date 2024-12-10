<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Enum;

enum DocumentFileUpdateStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
}
