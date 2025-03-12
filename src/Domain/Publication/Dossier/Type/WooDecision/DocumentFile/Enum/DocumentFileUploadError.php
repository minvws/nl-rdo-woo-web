<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum;

enum DocumentFileUploadError: string
{
    case MAX_SIZE_EXCEEDED = 'max_size_exceeded';
    case MALICIOUS_CONTENT = 'malicious_content';
}
