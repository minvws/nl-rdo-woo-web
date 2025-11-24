<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;

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
