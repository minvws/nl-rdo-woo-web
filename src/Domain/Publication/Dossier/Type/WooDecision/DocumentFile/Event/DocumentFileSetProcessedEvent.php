<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Event;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Symfony\Component\Uid\Uuid;

readonly class DocumentFileSetProcessedEvent
{
    public function __construct(
        public Uuid $dossierId,
    ) {
    }

    public static function forDocumentFileSet(DocumentFileSet $documentFileSet): self
    {
        return new self(
            $documentFileSet->getDossier()->getId(),
        );
    }
}
