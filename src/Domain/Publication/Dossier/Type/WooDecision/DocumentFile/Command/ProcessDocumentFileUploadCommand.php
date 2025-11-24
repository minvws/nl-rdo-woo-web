<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command;

use Symfony\Component\Uid\Uuid;

readonly class ProcessDocumentFileUploadCommand
{
    public function __construct(
        public Uuid $id,
    ) {
    }
}
