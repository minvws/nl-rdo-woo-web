<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Enum;

enum DocumentFileUpdateType: string
{
    case ADD = 'add';
    case UPDATE = 'update';
    case REPUBLISH = 'republish';
}
