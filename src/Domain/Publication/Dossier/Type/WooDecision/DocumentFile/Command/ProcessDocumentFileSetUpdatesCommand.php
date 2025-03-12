<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command;

use Symfony\Component\Uid\Uuid;

readonly class ProcessDocumentFileSetUpdatesCommand
{
    public function __construct(
        public Uuid $documentFileSetId,
    ) {
    }
}
