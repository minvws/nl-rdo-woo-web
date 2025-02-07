<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Command;

use Symfony\Component\Uid\Uuid;

readonly class ProcessDocumentFileSetUploadsCommand
{
    public function __construct(
        public Uuid $documentFileSetId,
    ) {
    }
}
