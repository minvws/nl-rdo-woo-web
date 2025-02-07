<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Enum;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;

enum DocumentFileUpdateType: string
{
    case ADD = 'add';
    case UPDATE = 'update';
    case REPUBLISH = 'republish';

    public static function forDocument(Document $document): self
    {
        if ($document->isWithdrawn()) {
            return self::REPUBLISH;
        }

        return $document->isUploaded() ? self::UPDATE : self::ADD;
    }
}
