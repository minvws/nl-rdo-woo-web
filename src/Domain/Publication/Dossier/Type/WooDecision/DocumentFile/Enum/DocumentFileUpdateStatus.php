<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum;

enum DocumentFileUpdateStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
}
