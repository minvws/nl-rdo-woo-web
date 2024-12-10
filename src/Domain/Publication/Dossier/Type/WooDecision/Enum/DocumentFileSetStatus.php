<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Enum;

enum DocumentFileSetStatus: string
{
    case OPEN_FOR_UPLOADS = 'open_for_uploads';
    case PROCESSING_UPLOADS = 'processing_uploads';
    case NEEDS_CONFIRMATION = 'needs_confirmation';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';
    case PROCESSING_UPDATES = 'processing_updates';
    case COMPLETED = 'completed';
}
